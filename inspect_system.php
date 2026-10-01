<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "--- HOURS TABLE ---" . PHP_EOL;
print_r(Schema::getColumnListing('hours'));

echo "--- PLAN_TYPES TABLE ---" . PHP_EOL;
print_r(Schema::getColumnListing('plan_types'));

echo "--- SAMPLE HOURS RECORDS ---" . PHP_EOL;
$sampleHours = App\Models\Hour::take(5)->get();
foreach ($sampleHours as $h) {
    echo json_encode($h) . PHP_EOL;
}

echo "--- SAMPLE PLAN_TYPES (Custom vs Standard) ---" . PHP_EOL;
$samplePT = App\Models\PlanType::take(10)->get(['id', 'name', 'day_type_id', 'slot_hours', 'start_time', 'end_time', 'branch_id']);
foreach ($samplePT as $pt) {
    echo json_encode($pt) . PHP_EOL;
}
