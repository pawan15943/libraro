<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "LearnerDetail Columns:\n";
print_r(DB::getSchemaBuilder()->getColumnListing('learner_detail'));

echo "\nHour table:\n";
print_r(DB::table('hour')->get()->toArray());

echo "\nPlanTypes table:\n";
print_r(DB::table('plan_types')->select('id', 'name', 'day_type_id', 'slot_hours', 'start_time', 'end_time')->get()->toArray());

echo "\nSample LearnerDetails:\n";
print_r(DB::table('learner_detail')->where('status', 1)->select('id', 'branch_id', 'learner_id', 'seat_no', 'plan_type_id', 'hour', 'status', 'plan_end_date')->limit(10)->get()->toArray());
