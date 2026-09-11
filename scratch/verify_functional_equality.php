<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\Library;
use App\Models\PlanType;
use App\Services\LearnerService;

$library = Library::where('status', 1)->first();
if (!$library) {
    echo "No active library found.\n";
    exit;
}

Auth::guard('library')->setUser($library);
session(['library_id' => $library->id]);
$branch = Branch::where('library_id', $library->id)->first();
if ($branch) {
    session(['branch_id' => $branch->id]);
    $library->update(['current_branch' => $branch->id]);
}

echo "=== FUNCTIONAL EQUALITY VERIFICATION ===\n\n";

$learnerService = app(LearnerService::class);
$optimizedResult = $learnerService->getAvailableSeatsPlantype();

// Unoptimized reference calculation
$firstRecord = Hour::where('branch_id', getCurrentBranch())->first();
$total_hour = $firstRecord ? $firstRecord->hour : null;
$total_seats = totalSeat();

$referenceResult = [];
for ($seatNo = 1; $seatNo <= $total_seats; $seatNo++) {
    $bookings = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->where('learner_detail.seat_no', $seatNo)
        ->where('learners.status', 1)
        ->where('learner_detail.status', 1)
        ->where('learners.branch_id', getCurrentBranch())
        ->where('learner_detail.branch_id', getCurrentBranch())
        ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

    $planTypes = PlanType::get();
    $planTypesRemovals = [];
    $totalBookedHours = $bookings->sum('slot_hours');
    $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->where('learner_detail.seat_no', $seatNo)
        ->where('learner_detail.status', 1)
        ->where('plan_types.day_type_id', 9)
        ->exists();

    if ($totalBookedHours < 24) {
        foreach ($bookings as $booking) {
            foreach ($planTypes as $planType) {
                if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                    $planTypesRemovals[] = $planType->id;
                }
            }
        }
    }

    if ($totalBookedHours > 1) {
        $planTypeId = PlanType::where('day_type_id', 8)->value('id') ?? 0;
        if (!is_null($planTypeId)) {
            $planTypesRemovals[] = $planTypeId;
        }
    }

    if ($nightseatBooked) {
        $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $seatNo)
            ->where('learner_detail.status', 1)
            ->where('plan_types.day_type_id', 9)
            ->value('plan_types.id') ?? 0;
        $planTypesRemovals[] = $planTypeid;
    }

    $planTypesRemovals = array_unique($planTypesRemovals);

    if ($total_hour !== null && $totalBookedHours >= $total_hour) {
        $planTypesRemovals = $planTypes->pluck('id')->toArray();
    }

    $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
        return !in_array($planType->id, $planTypesRemovals);
    })->map(function ($planType) {
        return ['id' => $planType->id, 'name' => $planType->name];
    })->values();

    $referenceResult[] = [
        'seat_no' => $seatNo,
        'seat_id' => $seatNo,
        'available_plan_types' => $filteredPlanTypes,
    ];
}

$isEqual = (json_encode($optimizedResult) === json_encode($referenceResult));

if ($isEqual) {
    echo "[SUCCESS] Optimized getAvailableSeatsPlantype output is 100% IDENTICAL to original output!\n";
} else {
    echo "[WARNING] Output discrepancy detected!\n";
    echo "Optimized Seats Count: " . count($optimizedResult) . ", Reference Seats Count: " . count($referenceResult) . "\n";
}
