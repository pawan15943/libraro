<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

echo "=== SUBSCRIPTION FEATURES COUNT ===\n";
$subscriptions = Subscription::with('permissions')->get();
$features = DB::table('subscription_plan_features')->where('feature_status', 1)->get();

foreach ($subscriptions as $s) {
    $subFeatures = $features->where('subscription_id', $s->id)->whereNull('deleted_at')->pluck('name')->toArray();
    $permCount = $s->permissions ? $s->permissions->count() : 0;
    echo "ID: {$s->id} | Name: {$s->name} | Features Count: " . count($subFeatures) . " | Perm Count: {$permCount}\n";
    echo "  Features: " . implode(', ', $subFeatures) . "\n";
}
