<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$branchId = 38; // Or active branch
echo "Current Branch: " . $branchId . "\n";

$firstRecord = DB::table('hour')->where('branch_id', $branchId)->first();
echo "Operating Hours Record:\n";
print_r($firstRecord);

$totalHour = $firstRecord ? $firstRecord->hour : 16;
$totalSeats = $firstRecord ? $firstRecord->seats : 50;

echo "Total Operating Hours: $totalHour, Total Seats: $totalSeats\n";

// Check learner_detail sum of hour
$usedSeatsHour = DB::table('learner_detail')
    ->where('branch_id', $branchId)
    ->where('status', 1)
    ->whereNotNull('seat_no')
    ->select('seat_no', DB::raw('SUM(hour) as used_hours'))
    ->groupBy('seat_no')
    ->pluck('used_hours', 'seat_no');

echo "\nUsed hours by learner_detail.hour:\n";
print_r($usedSeatsHour->toArray());

// Check joined with plan_types slot_hours
$usedSeatsSlotHours = DB::table('learner_detail')
    ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
    ->where('learner_detail.branch_id', $branchId)
    ->where('learner_detail.status', 1)
    ->whereNotNull('learner_detail.seat_no')
    ->select('learner_detail.seat_no', DB::raw('SUM(plan_types.slot_hours) as used_slot_hours'))
    ->groupBy('learner_detail.seat_no')
    ->pluck('used_slot_hours', 'learner_detail.seat_no');

echo "\nUsed hours by plan_types.slot_hours:\n";
print_r($usedSeatsSlotHours->toArray());
