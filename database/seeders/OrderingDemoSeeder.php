<?php

namespace Database\Seeders;

use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderItem;
use App\Services\Ordering\OrderingSetupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ordering & Services demo menu: 15 everyday items (tea, snacks, stationery, printing) with a photo-style picture,
 * so the learner menu and the order pages look real while testing.
 *
 *   php artisan db:seed --class=OrderingDemoSeeder                         every branch that opened Ordering
 *   ORDERING_DEMO_BRANCH=265 php artisan db:seed --class=OrderingDemoSeeder  one branch
 *
 * Safe to run again: an item that already exists (same name in the branch) is left as it is.
 * Pictures are SVG files written to public/upload/ordering/demo/<branch>/ (one folder per branch, so deleting an
 * item in one branch never removes another branch's picture). Nothing outside the order_* tables is touched.
 */
class OrderingDemoSeeder extends Seeder
{
    /** [name, category, price, unit, emoji, description, featured, needs upload] */
    private const ITEMS = [
        ['Masala Chai', 'Beverages', 15, 'cup', '☕', 'Hot ginger-cardamom tea, made fresh', true, false],
        ['Filter Coffee', 'Beverages', 25, 'cup', '☕', 'Strong South Indian coffee with milk', false, false],
        ['Cold Coffee', 'Beverages', 60, 'glass', '🧋', 'Chilled, creamy, lightly sweet', true, false],
        ['Veg Sandwich', 'Food', 50, null, '🥪', 'Grilled with cucumber, tomato and green chutney', true, false],
        ['Masala Maggi', 'Food', 45, 'plate', '🍜', 'Classic instant noodles with veggies', false, false],
        ['Poha', 'Food', 35, 'plate', '🥣', 'Light breakfast with peanuts and lemon', false, false],
        ['Veg Burger', 'Food', 70, null, '🍔', 'Crispy aloo patty, cheese slice, mayo', false, false],
        ['Samosa (2 pcs)', 'Snacks', 25, null, '🥟', 'Hot samosas with tamarind chutney', true, false],
        ['Chocolate Cookies', 'Snacks', 30, 'pack', '🍪', 'Pack of 6 crunchy cookies', false, false],
        ['Fruit Bowl', 'Snacks', 50, 'bowl', '🍉', 'Seasonal cut fruits, freshly made', false, false],
        ['Mineral Water 1L', 'Water', 20, 'bottle', '💧', 'Sealed packaged drinking water', false, false],
        ['Blue Ball Pen', 'Stationery', 10, null, '🖊️', 'Smooth writing, 0.7 mm', false, false],
        ['Notebook 200 pages', 'Stationery', 60, null, '📓', 'Ruled, A5 size', false, false],
        ['Highlighter', 'Study Essentials', 25, null, '🖍️', 'Fluorescent yellow, chisel tip', false, false],
        ['Black & White Print', 'Printing', 2, 'page', '🖨️', 'A4 print — send your PDF with the order', false, true],
    ];

    /** Background colours per category: [light, deep]. */
    private const TONES = [
        'Beverages' => ['#fde9d4', '#f59e0b'],
        'Food' => ['#ffe4dc', '#f97316'],
        'Snacks' => ['#fde2ea', '#ec4899'],
        'Water' => ['#dbf1fd', '#0ea5e9'],
        'Stationery' => ['#e0e7ff', '#6366f1'],
        'Study Essentials' => ['#ede4ff', '#8b5cf6'],
        'Printing' => ['#dcf5ef', '#0f9488'],
    ];

    public function run(): void
    {
        $only = (int) env('ORDERING_DEMO_BRANCH', 0);
        $branches = $only
            ? DB::table('branches')->where('id', $only)->get(['id', 'library_id'])
            : DB::table('branches')->whereIn('id', DB::table('order_settings')->pluck('branch_id'))->get(['id', 'library_id']);

        if ($branches->isEmpty()) {
            $this->command?->warn('No branch to seed. Open Orders → Menu & Items once in a branch, or set ORDERING_DEMO_BRANCH=<branch id>.');

            return;
        }

        $setup = app(OrderingSetupService::class);
        foreach ($branches as $b) {
            $setup->settings((int) $b->library_id, (int) $b->id); // creates the starter categories the first time
            $cats = OrderCategory::withoutGlobalScopes()->where('branch_id', $b->id)->pluck('id', 'name');
            $dir = 'upload/ordering/demo/'.$b->id;
            @mkdir(public_path($dir), 0775, true);

            $added = 0;
            foreach (self::ITEMS as $i => [$name, $cat, $price, $unit, $emoji, $desc, $featured, $upload]) {
                if (! isset($cats[$cat])) {
                    continue; // the owner deleted or renamed this category
                }
                if (OrderItem::withoutGlobalScopes()->where('branch_id', $b->id)->where('name', $name)->exists()) {
                    continue;
                }
                $file = $dir.'/'.Str::slug($name).'.svg';
                file_put_contents(public_path($file), $this->picture($emoji, self::TONES[$cat] ?? ['#eef2f7', '#64748b']));

                OrderItem::withoutGlobalScopes()->create([
                    'library_id' => $b->library_id,
                    'branch_id' => $b->id,
                    'order_category_id' => $cats[$cat],
                    'name' => $name,
                    'description' => $desc,
                    'image' => 'public/'.$file,
                    'price' => $price,
                    'price_unit' => $unit,
                    'is_enabled' => true,
                    'is_available' => true,
                    'is_featured' => $featured,
                    'needs_upload' => $upload,
                    'sort_order' => $i + 1,
                ]);
                $added++;
            }
            $this->command?->info("Branch {$b->id}: {$added} demo items added.");
        }
    }

    /** A square food-app style picture: soft gradient, two light circles, the emoji large in the middle. */
    private function picture(string $emoji, array $tone): string
    {
        [$light, $deep] = $tone;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="480" height="480" viewBox="0 0 480 480">
  <defs>
    <radialGradient id="bg" cx="35%" cy="30%" r="85%">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="55%" stop-color="{$light}"/>
      <stop offset="100%" stop-color="{$deep}" stop-opacity="0.55"/>
    </radialGradient>
    <radialGradient id="sh" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#000" stop-opacity="0.22"/>
      <stop offset="100%" stop-color="#000" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="480" height="480" fill="url(#bg)"/>
  <circle cx="400" cy="70" r="90" fill="#ffffff" opacity="0.35"/>
  <circle cx="60" cy="430" r="110" fill="{$deep}" opacity="0.12"/>
  <ellipse cx="240" cy="372" rx="130" ry="22" fill="url(#sh)"/>
  <text x="240" y="300" text-anchor="middle" font-size="210" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif">{$emoji}</text>
</svg>
SVG;
    }
}
