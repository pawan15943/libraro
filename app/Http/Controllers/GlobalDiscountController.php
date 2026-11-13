<?php

namespace App\Http\Controllers;

use App\Models\GlobalDiscount;
use App\Models\LearnerTransaction;
use App\Services\GlobalDiscountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Setup → Global Discount (owner only). One library-wide offer that every booking
 * form applies by itself between its start and end date (docs/GLOBAL_DISCOUNT.md).
 */
class GlobalDiscountController extends Controller
{
    public function __construct(private GlobalDiscountService $offers) {}

    private function mine(int $id): GlobalDiscount
    {
        return GlobalDiscount::withoutGlobalScopes()->whereNull('deleted_at')->where('library_id', getLibraryId())->findOrFail($id);
    }

    public function index()
    {
        $libraryId = getLibraryId();
        $order = ['running' => 0, 'scheduled' => 1, 'off' => 2, 'ended' => 3];
        $discounts = GlobalDiscount::withoutGlobalScopes()->whereNull('deleted_at')->where('library_id', $libraryId)
            ->orderByDesc('start_date')->orderByDesc('id')->get()
            ->sortBy(fn ($d) => $order[$d->state()])->values();

        $usage = LearnerTransaction::withoutGlobalScopes()->whereNull('deleted_at')->where('library_id', $libraryId)
            ->whereNotNull('global_discount_id')->where('global_discount_amount', '>', 0)
            ->groupBy('global_discount_id')
            ->selectRaw('global_discount_id, COUNT(*) as bookings, SUM(global_discount_amount) as saved')
            ->get()->keyBy('global_discount_id');

        $running = $this->offers->active($libraryId);

        return view('library.global-discount.index', [
            'discounts' => $discounts,
            'usage' => $usage,
            'running' => $running,
            'scheduledCount' => $discounts->filter(fn ($d) => $d->state() === 'scheduled')->count(),
            'totalBookings' => (int) $usage->sum('bookings'),
            'totalSaved' => (float) $usage->sum('saved'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        GlobalDiscount::create($data + ['library_id' => getLibraryId(), 'created_by' => Auth::guard('library')->id()]);

        return redirect()->route('global-discount.index')->with('success', $this->savedMessage($data));
    }

    public function update(Request $request, int $id)
    {
        $discount = $this->mine($id);
        $data = $this->validated($request, $discount->id);
        $discount->update($data);

        return redirect()->route('global-discount.index')->with('success', $this->savedMessage($data));
    }

    public function toggle(int $id)
    {
        $discount = $this->mine($id);
        if (! $discount->is_enabled && ($clash = $this->offers->overlapping($discount->library_id, $discount->start_date, $discount->end_date, $discount->id))) {
            return back()->with('error', 'Turn off "'.$clash->name.'" first. Its dates overlap with this discount, and only one discount can run at a time.');
        }
        $discount->update(['is_enabled' => ! $discount->is_enabled]);

        return back()->with('success', $discount->is_enabled ? 'Discount turned on.' : 'Discount turned off. Booking forms no longer apply it.');
    }

    public function destroy(int $id)
    {
        $this->mine($id)->delete();

        return redirect()->route('global-discount.index')->with('success', 'Discount deleted. Bookings that already got it keep their price.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->merge(['form_id' => $ignoreId ?: 'new']);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'discount_type' => 'required|in:percentage,amount',
            'discount_value' => ['required', 'numeric', 'gt:0', $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:9999999'],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'name.required' => 'Give the discount a name, e.g. Diwali Offer.',
            'discount_value.gt' => 'The discount must be more than 0.',
            'discount_value.max' => $request->input('discount_type') === 'percentage' ? 'A percent discount can be at most 100%.' : 'This amount is too large.',
            'end_date.after_or_equal' => 'The end date cannot be before the start date.',
        ]);
        $data['name'] = trim($data['name']);
        $data['is_enabled'] = $request->boolean('is_enabled', true);

        if ($data['is_enabled'] && ($clash = $this->offers->overlapping(getLibraryId(), $data['start_date'], $data['end_date'], $ignoreId))) {
            throw ValidationException::withMessages([
                'start_date' => '"'.$clash->name.'" already runs from '.$clash->start_date->format('j M Y').' to '.$clash->end_date->format('j M Y').'. Only one discount can run at a time, so pick other dates or turn that one off.',
            ]);
        }

        return $data;
    }

    private function savedMessage(array $data): string
    {
        if (! $data['is_enabled']) {
            return 'Discount saved. It is turned off, so booking forms will not apply it.';
        }
        $today = today()->toDateString();
        if ($data['start_date'] > $today) {
            return 'Discount saved. It starts by itself on '.\Carbon\Carbon::parse($data['start_date'])->format('j M Y').'.';
        }

        return $data['end_date'] < $today
            ? 'Discount saved. Its end date has passed, so it will not apply.'
            : 'Discount saved and running. Every booking form now applies it, and students see it on their dashboard.';
    }
}
