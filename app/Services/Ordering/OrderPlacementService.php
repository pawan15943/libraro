<?php

namespace App\Services\Ordering;

use App\Models\Learner;
use App\Models\Ordering\Order;
use App\Models\Ordering\OrderActivity;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderLine;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Turns a learner's cart into an order (spec §4 step 7). Everything is checked again on the
 * server: module on, hours, eligibility, open-order limit, location, items, latest prices,
 * payment method (allowed by settings AND every category in the cart), uploads.
 * The same idempotency key never makes a second order (double click / retry after a timeout).
 */
class OrderPlacementService
{
    public const MAX_QTY = 50;

    public const MAX_LINES = 30;

    public function __construct(private LearnerOrderingService $ordering) {}

    /**
     * @param  array  $data  mode, seat_no?, order_table_id?, payment_method, note?, allow_partial?, idempotency_key,
     *                       expected_total?, lines[] = [item_id, qty, copies?, sides?, note?]
     * @param  array<int, UploadedFile|null>  $files  line index → uploaded document
     *
     * @throws ValidationException
     * @throws PriceChangedException
     */
    public function place(Learner $learner, array $data, array $files = []): Order
    {
        $key = substr(trim((string) ($data['idempotency_key'] ?? '')), 0, 64);
        if ($key === '') {
            throw ValidationException::withMessages(['idempotency_key' => 'Please reload the page and try again.']);
        }
        if ($existing = $this->existing($learner, $key)) {
            return $existing; // the first click already placed it
        }

        $ctx = $this->ordering->context($learner);
        $s = $ctx['settings'];

        if (! $ctx['eligible']) {
            throw ValidationException::withMessages(['order' => $ctx['reason']]);
        }
        if (! $ctx['open']) {
            throw ValidationException::withMessages(['order' => 'Orders are taken from '.$ctx['opens_at'].' to '.$ctx['closes_at'].'. Please order then.']);
        }
        if ($ctx['limit_reached']) {
            throw ValidationException::withMessages(['order' => 'You already have '.$ctx['open_orders'].' orders in progress. Please wait until one is done.']);
        }

        // ---- location
        $mode = (string) ($data['mode'] ?? '');
        if (! in_array($mode, $ctx['modes'], true)) {
            throw ValidationException::withMessages(['mode' => 'Choose where you want your order.']);
        }
        $seatNo = null;
        $table = null;
        if ($mode === 'seat') {
            $typed = trim((string) ($data['seat_no'] ?? ''));
            $seatNo = ($ctx['seat_editable'] && $typed !== '') ? mb_substr($typed, 0, 20) : $ctx['seat'];
            if (! $seatNo) {
                throw ValidationException::withMessages(['seat_no' => 'Enter your seat number.']);
            }
        } elseif ($mode === 'table') {
            $table = $ctx['tables']->firstWhere('id', (int) ($data['order_table_id'] ?? 0));
            if (! $table) {
                throw ValidationException::withMessages(['order_table_id' => 'Choose your table.']);
            }
        }

        // ---- lines: latest item data from the database, never the browser's
        $raw = array_values(array_filter((array) ($data['lines'] ?? []), 'is_array'));
        if (! $raw) {
            throw ValidationException::withMessages(['lines' => 'Your cart is empty.']);
        }
        if (count($raw) > self::MAX_LINES) {
            throw ValidationException::withMessages(['lines' => 'Too many items in one order.']);
        }
        $ids = array_unique(array_map(fn ($l) => (int) ($l['item_id'] ?? 0), $raw));
        $items = OrderItem::withoutGlobalScopes()->whereNull('deleted_at')
            ->where('branch_id', $learner->branch_id)->whereIn('id', $ids)
            ->with(['category' => fn ($q) => $q->withoutGlobalScopes()])
            ->get()->keyBy('id');

        $lines = [];
        $merged = []; // item id → index in $lines (same item twice = more quantity, except uploads)
        foreach ($raw as $i => $l) {
            $item = $items->get((int) ($l['item_id'] ?? 0));
            if (! $item || ! $item->is_enabled || ! $item->category || $item->category->trashed() || ! $item->category->is_enabled) {
                throw ValidationException::withMessages(['lines' => 'An item in your cart is no longer on the menu. Please remove it and try again.']);
            }
            if (! $item->is_available) {
                throw ValidationException::withMessages(['lines' => $item->name.' is not available right now. Please remove it from your cart.']);
            }
            if (! $item->allowsMode($mode)) {
                throw ValidationException::withMessages(['lines' => $item->name.' cannot be ordered for '.strtolower(\App\Models\Ordering\OrderSetting::MODES[$mode]).'.']);
            }
            $qty = (int) ($l['qty'] ?? 1);
            if ($qty < 1 || $qty > self::MAX_QTY) {
                throw ValidationException::withMessages(['lines' => 'Quantity of '.$item->name.' must be 1 to '.self::MAX_QTY.'.']);
            }

            if ($item->needs_upload) {
                $file = $files[$i] ?? null;
                $this->checkUpload($file, $item, $s);
                $copies = max(1, min(50, (int) ($l['copies'] ?? 1)));
                $sides = ($l['sides'] ?? 'single') === 'double' ? 'double' : 'single';
                $lines[] = [
                    'item' => $item, 'qty' => $qty, 'file' => $file,
                    'options' => ['pages' => $qty, 'copies' => $copies, 'sides' => $sides],
                    'units' => $qty * $copies,
                    'note' => mb_substr(trim((string) ($l['note'] ?? '')), 0, 255) ?: null,
                ];

                continue;
            }

            if (isset($merged[$item->id])) {
                $lines[$merged[$item->id]]['qty'] = min(self::MAX_QTY, $lines[$merged[$item->id]]['qty'] + $qty);
                $lines[$merged[$item->id]]['units'] = $lines[$merged[$item->id]]['qty'];

                continue;
            }
            $merged[$item->id] = count($lines);
            $lines[] = [
                'item' => $item, 'qty' => $qty, 'file' => null, 'options' => null, 'units' => $qty,
                'note' => mb_substr(trim((string) ($l['note'] ?? '')), 0, 255) ?: null,
            ];
        }

        $subtotal = round(array_sum(array_map(fn ($l) => (float) $l['item']->price * $l['units'], $lines)), 2);
        $delivery = in_array($mode, ['seat', 'table'], true) ? round((float) $s->delivery_charge, 2) : 0.0;
        $total = round($subtotal + $delivery, 2);

        // price changed while the learner was shopping → show the new prices, do not charge silently
        if (isset($data['expected_total']) && $data['expected_total'] !== '' && abs((float) $data['expected_total'] - $total) > 0.009) {
            throw new PriceChangedException($total, $items->map(fn ($it) => (float) $it->price)->all());
        }

        // ---- payment: on in settings, usable by this learner, allowed by every category in the cart
        $method = (string) ($data['payment_method'] ?? '');
        $allowed = $this->allowedPayments($ctx['payments'], collect($lines)->pluck('item'));
        if (! in_array($method, $allowed, true)) {
            throw ValidationException::withMessages(['payment_method' => $allowed
                ? 'Choose one of the payment methods shown.'
                : 'The items in your cart have no common payment method. Please order them separately.']);
        }
        if ($method === 'pay_later' && $ctx['pay_later_room'] !== null && $total > $ctx['pay_later_room']) {
            throw ValidationException::withMessages(['payment_method' => 'Pay later is up to ₹'.number_format($ctx['pay_later_room'], 2).' for you right now. Please choose another method.']);
        }

        // ---- save (files first, removed again if the order is not saved)
        $stored = [];
        try {
            foreach ($lines as $idx => $l) {
                if ($l['file']) {
                    $stored[$idx] = [
                        'path' => $l['file']->store('ordering/'.$learner->branch_id.'/'.now()->format('Ym'), 'local'),
                        'name' => mb_substr($l['file']->getClientOriginalName(), 0, 190),
                    ];
                }
            }

            $order = $this->create($learner, $key, [
                'mode' => $mode,
                'seat_no' => $seatNo,
                'order_table_id' => $table?->id,
                'table_label' => $table?->label,
                'payment_method' => $method,
                'subtotal' => $subtotal,
                'delivery_charge' => $delivery,
                'total' => $total,
                'allow_partial' => $s->partial_fulfilment ? (bool) ($data['allow_partial'] ?? true) : false,
                'note' => mb_substr(trim((string) ($data['note'] ?? '')), 0, 500) ?: null,
                'estimated_ready_at' => now()->addMinutes((int) $s->prep_minutes),
            ], $lines, $stored);
        } catch (\Throwable $e) {
            foreach ($stored as $f) {
                Storage::disk('local')->delete($f['path']);
            }
            throw $e;
        }

        return $order;
    }

    /** Payment methods usable for a cart: learner's methods ∩ every category's methods. */
    public function allowedPayments(array $learnerMethods, $items): array
    {
        $allowed = $learnerMethods;
        foreach ($items as $item) {
            $allowed = array_values(array_intersect($allowed, $item->category->allowedPayments()));
        }

        return $allowed;
    }

    private function existing(Learner $learner, string $key): ?Order
    {
        return Order::withoutGlobalScopes()->where('learner_id', $learner->id)->where('idempotency_key', $key)->first();
    }

    private function checkUpload(?UploadedFile $file, OrderItem $item, $s): void
    {
        if (! $file || ! $file->isValid()) {
            throw ValidationException::withMessages(['lines' => 'Attach the file for '.$item->name.'.']);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $s->uploadExtensions(), true)) {
            throw ValidationException::withMessages(['lines' => 'For '.$item->name.' send a '.strtoupper(implode(', ', $s->uploadExtensions())).' file.']);
        }
        if ($file->getSize() > (int) $s->upload_max_mb * 1024 * 1024) {
            throw ValidationException::withMessages(['lines' => 'The file for '.$item->name.' is bigger than '.$s->upload_max_mb.' MB.']);
        }
    }

    private function create(Learner $learner, string $key, array $fields, array $lines, array $stored): Order
    {
        for ($try = 0; $try < 5; $try++) {
            try {
                return DB::transaction(function () use ($learner, $key, $fields, $lines, $stored) {
                    $order = Order::withoutGlobalScopes()->create($fields + [
                        'library_id' => $learner->library_id,
                        'branch_id' => $learner->branch_id,
                        'learner_id' => $learner->id,
                        'order_no' => $this->nextNumber((int) $learner->branch_id),
                        'idempotency_key' => $key,
                        'status' => Order::NEW,
                        'payment_status' => Order::PAY_PENDING,
                    ]);

                    foreach ($lines as $idx => $l) {
                        $item = $l['item'];
                        OrderLine::create([
                            'order_id' => $order->id,
                            'order_item_id' => $item->id,
                            'order_category_id' => $item->order_category_id,
                            'category_name' => $item->category->name,
                            'item_name' => $item->name,
                            'unit_price' => $item->price,
                            'quantity' => $l['qty'],
                            'line_total' => round((float) $item->price * $l['units'], 2),
                            'options' => $l['options'],
                            'upload_path' => $stored[$idx]['path'] ?? null,
                            'upload_name' => $stored[$idx]['name'] ?? null,
                            'note' => $l['note'],
                        ]);
                    }

                    OrderActivity::log($order, 'placed', null, Order::NEW, [
                        'total' => (float) $order->total, 'payment_method' => $order->payment_method, 'mode' => $order->mode,
                    ]);

                    return $order;
                });
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
                // same idempotency key from a parallel request → that order wins
                if ($existing = $this->existing($learner, $key)) {
                    return $existing;
                }
                // order number taken by a parallel order → try the next one
            }
        }

        throw ValidationException::withMessages(['order' => 'Could not place the order. Please try again.']);
    }

    /** OD<branch>-<yymmdd>-<nnn>: short daily number staff can call out ("order 7"). */
    private function nextNumber(int $branchId): string
    {
        $prefix = 'OD'.$branchId.'-'.now()->format('ymd').'-';
        $last = Order::withoutGlobalScopes()->where('order_no', 'like', $prefix.'%')->orderByDesc('id')->value('order_no');
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
