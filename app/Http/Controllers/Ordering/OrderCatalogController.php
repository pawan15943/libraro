<?php

namespace App\Http\Controllers\Ordering;

use App\Http\Controllers\Controller;
use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Models\Ordering\OrderSetting;
use App\Services\FileUploadService;
use App\Services\Ordering\OrderingSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

/**
 * Library panel → Orders → Menu & Items: categories and the items / services learners can order
 * (spec §2, §7A). No stock counting — an item is simply available or not.
 */
class OrderCatalogController extends Controller
{
    public function __construct(private OrderingSetupService $setup) {}

    private function branchId(): int
    {
        return (int) getCurrentBranch();
    }

    private function category(int $id): OrderCategory
    {
        return OrderCategory::where('library_id', getLibraryId())->where('branch_id', $this->branchId())->findOrFail($id);
    }

    private function item(int $id): OrderItem
    {
        return OrderItem::where('library_id', getLibraryId())->where('branch_id', $this->branchId())->findOrFail($id);
    }

    public function index()
    {
        $settings = $this->setup->current(); // also seeds the starter categories the first time

        $categories = OrderCategory::where('library_id', getLibraryId())->where('branch_id', $this->branchId())
            ->with('items')->orderBy('sort_order')->orderBy('name')->get();

        $stats = [
            'items' => $categories->sum(fn ($c) => $c->items->count()),
            'available' => $categories->sum(fn ($c) => $c->items->where('is_enabled', true)->where('is_available', true)->count()),
            'off' => $categories->sum(fn ($c) => $c->items->where('is_available', false)->count()),
            'categories' => $categories->where('is_enabled', true)->count(),
        ];

        return view('ordering.library.items', [
            'settings' => $settings,
            'categories' => $categories,
            'stats' => $stats,
            'icons' => OrderCategory::ICONS,
            'modes' => OrderSetting::MODES,
        ]);
    }

    // ---- categories -------------------------------------------------------------------------

    private function categoryRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:80',
                Rule::unique('order_categories', 'name')->ignore($ignoreId)
                    ->where('branch_id', $this->branchId())->whereNull('deleted_at')],
            'icon' => ['nullable', Rule::in(OrderCategory::ICONS)],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
    }

    private function categoryFlags(Request $request): array
    {
        $flags = [
            'is_enabled' => $request->boolean('is_enabled'),
            'is_service' => $request->boolean('is_service'),
            'allow_online' => $request->boolean('allow_online'),
            'allow_offline' => $request->boolean('allow_offline'),
            'allow_pay_later' => $request->boolean('allow_pay_later'),
        ];
        if (! $flags['allow_online'] && ! $flags['allow_offline'] && ! $flags['allow_pay_later']) {
            // a category nobody can pay for could never be ordered
            $flags['allow_offline'] = true;
        }

        return $flags;
    }

    public function categoryStore(Request $request)
    {
        $data = $request->validate($this->categoryRules());
        OrderCategory::create([
            'library_id' => getLibraryId(),
            'branch_id' => $this->branchId(),
            'name' => trim($data['name']),
            'icon' => $data['icon'] ?? 'shapes',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ] + $this->categoryFlags($request));

        return back()->with('success', 'Category added.');
    }

    public function categoryUpdate(Request $request, int $id)
    {
        $category = $this->category($id);
        $data = $request->validate($this->categoryRules($category->id));
        $category->update([
            'name' => trim($data['name']),
            'icon' => $data['icon'] ?? $category->icon,
            'sort_order' => (int) ($data['sort_order'] ?? $category->sort_order),
        ] + $this->categoryFlags($request));

        return back()->with('success', 'Category saved.');
    }

    public function categoryToggle(int $id)
    {
        $category = $this->category($id);
        $category->update(['is_enabled' => ! $category->is_enabled]);

        return back()->with('success', $category->is_enabled ? $category->name.' is visible to learners.' : $category->name.' is hidden from learners.');
    }

    public function categoryDestroy(int $id)
    {
        $category = $this->category($id);
        $category->items()->delete(); // soft: past orders keep their snapshot
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    // ---- items ------------------------------------------------------------------------------

    private function itemRules(): array
    {
        return [
            'order_category_id' => ['required', 'integer',
                Rule::exists('order_categories', 'id')->where('library_id', getLibraryId())
                    ->where('branch_id', $this->branchId())->whereNull('deleted_at')],
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0|max:99999',
            'price_unit' => 'nullable|string|max:30',
            'modes' => 'nullable|array',
            'modes.*' => [Rule::in(array_keys(OrderSetting::MODES))],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
    }

    private function itemFields(Request $request, array $data): array
    {
        $modes = array_values(array_unique($data['modes'] ?? []));

        return [
            'order_category_id' => (int) $data['order_category_id'],
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'price' => round((float) $data['price'], 2),
            'price_unit' => trim((string) ($data['price_unit'] ?? '')) ?: null,
            // every mode ticked (or none) = no limit
            'modes' => ($modes && count($modes) < count(OrderSetting::MODES)) ? $modes : null,
            'is_enabled' => $request->boolean('is_enabled'),
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
            'needs_upload' => $request->boolean('needs_upload'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return app(FileUploadService::class)->moveTempFileToPublic($request->file('image'), 'item', 'upload/ordering');
    }

    private function dropImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'public/upload/ordering/')) {
            File::delete(base_path($path));
        }
    }

    public function itemStore(Request $request)
    {
        $data = $request->validate($this->itemRules());
        OrderItem::create([
            'library_id' => getLibraryId(),
            'branch_id' => $this->branchId(),
            'image' => $this->storeImage($request),
        ] + $this->itemFields($request, $data));

        return back()->with('success', 'Item added.');
    }

    public function itemUpdate(Request $request, int $id)
    {
        $item = $this->item($id);
        $data = $request->validate($this->itemRules());
        $fields = $this->itemFields($request, $data);

        if ($new = $this->storeImage($request)) {
            $this->dropImage($item->image);
            $fields['image'] = $new;
        } elseif ($request->boolean('remove_image')) {
            $this->dropImage($item->image);
            $fields['image'] = null;
        }
        $item->update($fields);

        return back()->with('success', 'Item saved.');
    }

    /** One-tap "in stock / out of stock" from the list (AJAX or form). */
    public function itemAvailability(Request $request, int $id)
    {
        $item = $this->item($id);
        $item->update(['is_available' => $request->has('is_available') ? $request->boolean('is_available') : ! $item->is_available]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'is_available' => $item->is_available]);
        }

        return back()->with('success', $item->name.($item->is_available ? ' is available.' : ' is marked unavailable.'));
    }

    public function itemDestroy(int $id)
    {
        $item = $this->item($id);
        $item->delete(); // soft: past orders keep their snapshot, image kept for them

        return back()->with('success', 'Item deleted.');
    }
}
