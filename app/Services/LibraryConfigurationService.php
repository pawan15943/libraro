<?php
namespace App\Services;

use App\Models\Branch;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LibraryConfigurationService
{
   
    public function configure($request,array $validated,int $libraryId,$existingBranch,int $branchCount)
    {
        DB::beginTransaction();

        try {

           /* ======================
           HANDLE LOGO UPLOAD
            ======================= */
           // Remove raw uploaded file from validated
            unset($validated['library_logo']);

            if ($request->hasFile('library_logo')) {
                $validated['library_logo'] = $request->file('library_logo')
                    ->store('uploads/logo', 'public');
            }

            /* ======================
            HANDLE LIBRARY IMAGES
            ======================= */
           

            if ($request->hasFile('library_images')) {
                foreach ($request->file('library_images') as $image) {
                    $validated['library_images'][] =
                        $image->store('uploads/library_images', 'public');
                }
            }

            
           $validated['library_id'] = $libraryId;
            $validated['display_name'] = $validated['display_name'] ?? $validated['name'];

           $floorses= $validated['floors'];
          
           $plans = $validated['plans'] ?? [];

            $hour  = $validated['hour'];
            $seats = $validated['seats'];
            unset($validated['hour'], $validated['seats']);
            $slug = \Str::slug($validated['name'].'-'.$libraryId);

            /* =========================
            FLOOR VALIDATION
            ========================= */
            $floors = collect($floorses)
                ->filter(fn ($floor) =>
                    filled($floor['name']) ||
                    filled($floor['from']) ||
                    filled($floor['to'])
                )
                ->values()
                ->toArray();

            $totalFloorSeats = 0;
          

            foreach ($floors as $index => $floor) {
                 //  If from/to is filled, name is required
                if ((filled($floor['from']) || filled($floor['to'])) && empty($floor['name'])) {
                    throw new \Exception(
                        "Floor name is required when seat range is provided (Row ".($index + 1).")"
                    );
                }

                if (empty($floor['from']) || empty($floor['to'])) {
                    throw new \Exception(
                        'Seat range is required for each floor.'
                    );
                }

                if ($floor['to'] < $floor['from']) {
                    throw new \Exception(
                        'Seat To must be greater than or equal to Seat From.'
                    );
                }

                $totalFloorSeats += ($floor['to'] - $floor['from']) + 1;
            }

            if ($totalFloorSeats > $seats) {
                throw new \Exception(
                    "Total floor seats ({$totalFloorSeats}) cannot exceed branch seats ({$seats})"
                );
            }

            if (isset($validated['library_logo']) && $validated['library_logo'] instanceof \Illuminate\Http\UploadedFile) {
                unset($validated['library_logo']);
            }

            /* =========================
            CREATE BRANCH
            ========================= */
            $branchData = collect($validated)->except([
                'plans',
                'monthdays',
                'floors',
            ])->toArray();

            $branch = $existingBranch ?? new Branch();
            
            $branch->fill($branchData);
            $branch->library_id = $libraryId;

            if (!empty($validated['library_logo'])) {
              
                $branch->library_logo = $validated['library_logo'];
            }
            
            
          
           
            if (!empty($validated['features'])) {
                $branch->features = json_encode($validated['features']);
            }

            $branch->google_map = $validated['google_map'] ?? null;
            $branch->slug = $slug;
            $branch->save();

            /* =========================
            HOURS
            ========================= */
             Hour::updateOrCreate(
                [
                    'branch_id'  => $branch->id,
                    'library_id' => $libraryId,
                ],
                [
                    'hour'  => $hour,
                    'seats' => $seats,
                ]
            );

            /* =========================
            PLANS
            ========================= */
           if ($existingBranch || $branchCount == 0){
            
                    // DELETE REMOVED PLANS
                Plan::where('library_id', $libraryId)
                    ->whereNotIn('name', $plans)
                    ->delete();

                $baseMonthDays = null;
                foreach ($plans as $plan) {
                    [$num, $type] = explode(' ', $plan);
                    if ((int)$num === 1 && strtoupper($type) === 'MONTH') {
                         $baseMonthDays = $validated['monthdays'] ?? null;
                        break;
                    }
                }

                foreach ($plans as $plan) {
                    [$num, $type] = explode(' ', $plan);

                    Plan::updateOrCreate(
                        [
                            'library_id' =>  $libraryId,
                            'name'       => $plan,
                        ],
                        [
                            'plan_id'   => (int)$num,
                            'type'      => strtoupper($type),
                            'monthdays' => strtoupper($type) === 'MONTH'
                                ? $baseMonthDays
                                : null,
                        ]
                    );
                }
            }

            /* =========================
            FLOORS
            ========================= */
            Floor::where('branch_id', $branch->id)->delete();

            foreach ($floors as $index => $floor) {
                Floor::create([
                    'branch_id'   => $branch->id,
                    'name'        => $floor['name'],
                    'floor_no'    => $index + 1,
                    'from_seat'   => (int)$floor['from'],
                    'to_seat'     => (int)$floor['to'],
                    
                ]);
            }

            /* =========================
            LIBRARY IMAGES
            ========================= */
             
            Library::where('id', $libraryId,)->update([
                'current_branch'=> $branch->id
            ]);

            DB::commit();

            return [
                'status'   => true,
                'message'  => 'Branch added successfully.',
                'branch_id'=> $branch->id
            ];

        } catch (\Exception $e) {

            DB::rollBack();

           return [
            'status'  => false,
            'message' => $e->getMessage()
        ];
        }
    }

    public function shiftConfigure(array $validated,int $branchId)
    {
        DB::beginTransaction();

        try {

            $branch = Branch::find($branchId);
             if (!$branch) {
                throw new \Exception('Invalid branch.');
            }
            $libraryId=$branch->library_id;
            $plan = Plan::where('library_id', $libraryId)
                ->where('plan_id', 1)
                ->where('type', 'MONTH')
                ->first();

            if (!$plan) {
                throw new \Exception('Ops, System not found any plan to proceed for shifts.');
            }

            $branchRecord = Hour::where('branch_id', $branchId)->first();

            $existingPlanTypeCount = PlanType::where('branch_id', $branchId)->count();
            $isFirstTimeSetup = $existingPlanTypeCount === 0;

            $finalShiftIds = [];
            $coveredMinutes = [];

            /* ================= GLOBAL COVERAGE CHECK ================= */

            foreach ($validated['plan_types'] as $row) {

                $start = Carbon::parse($row['start_time']);
                $end   = Carbon::parse($row['end_time']);

                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                while ($start < $end) {
                    $coveredMinutes[$start->format('H:i')] = true;
                    $start->addMinute();
                }
            }

            $totalCoveredHours = count($coveredMinutes) / 60;

            if ($branchRecord->hour != 24 && $totalCoveredHours > $branchRecord->hour) {
                throw new \Exception('Shift timing exceeds library hours.');
            }

            /* ================= CREATE / UPDATE ================= */

            $timePairs = [];
            $isCreating = false;
            $isUpdating = false;
            $planTypesss = $validated['plan_types'];

            foreach ($planTypesss as $index => $row) {

                if ($row['slot_hours'] > $branchRecord->hour && $branchRecord->hour != 24) {
                    throw new \Exception('Selected hours exceed the library’s available hours.');
                }

                $start = Carbon::parse($row['start_time']);
                $end   = Carbon::parse($row['end_time']);

                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                $actualHours = $start->diffInHours($end);

                if ($row['slot_hours'] != $actualHours) {
                    throw new \Exception(
                        "Slot hours must match shift time ({$actualHours} hours)."
                    );
                }

                $dayTypeId = (int) $row['day_type_id'];

                if (in_array($dayTypeId, [10, 11])) {
                    if ($actualHours != $branchRecord->hour) {
                        $shiftName = $dayTypeId == 11 ? 'VIP' : 'Reserved';
                        throw new \Exception(
                            "{$shiftName} shift must match library timing ({$branchRecord->hour} hours)."
                        );
                    }
                }

                $rowId = $row['plan_type_id'] ?? 'new';
                $currentDayType = (int) $row['day_type_id'];

                if ($currentDayType === 0) {
                    $pairKey = $row['start_time'].'-'.$row['end_time'];

                    if (isset($timePairs['custom'][$pairKey])) {
                        throw new \Exception(
                            'Duplicate custom shift detected for same time range.'
                        );
                    }

                    $timePairs['custom'][$pairKey] = true;

                } else {

                    if (isset($timePairs['non_custom'][$currentDayType])) {
                        throw new \Exception('Duplicate shift detected.');
                    }

                    $timePairs['non_custom'][$currentDayType] = true;
                }

                $currentId = $row['plan_type_id'] ?? null;

                /* ================= DB DUPLICATE CHECK ================= */

                if ($row['day_type_id'] == 0) {

                    $existing = PlanType::where('branch_id', $branchId)
                        ->where('day_type_id', 0)
                        ->where('start_time', $row['start_time'])
                        ->where('end_time', $row['end_time'])
                        ->first();

                } else {

                    $existing = PlanType::where('branch_id', $branchId)
                        ->where('day_type_id', $row['day_type_id'])
                        ->first();
                }

                if ($existing) {

                    if (!$currentId) {
                        $planTypesss[$index]['plan_type_id'] = $existing->id;
                        $row['plan_type_id'] = $existing->id;
                        $currentId = $existing->id;
                    }

                    elseif ($existing->id != $currentId) {
                        throw new \Exception(
                            $row['day_type_id'] == 0
                                ? 'Custom shift already exists for this time range.'
                                : 'This shift type already exists.'
                        );
                    }
                }

                $planTypeName = match ((int)$row['day_type_id']) {
                    1 => 'Full Day',
                    2 => 'First Half',
                    3 => 'Second Half',
                    8 => 'All Day',
                    9 => 'Full Night',
                    10 => 'Reserved',
                    11 => 'VIP',
                    0 => $row['custom_plan_type'],
                    default => 'Custom',
                };
                $shiftId = $currentId;

                if ($shiftId) {

                    $isUpdating = true;

                    $planType = PlanType::where('id', $shiftId)
                        ->where('branch_id', $branchId)
                        ->first();

                    if (!$planType) {
                        throw new \Exception('Invalid shift selected.');
                    }

                    $planType->update([
                        'library_id'  => $libraryId,
                        'branch_id'   => $branchId,
                        'day_type_id' => $row['day_type_id'],
                        'name'        => $planTypeName,
                        'start_time'  => $row['start_time'],
                        'end_time'    => $row['end_time'],
                        'slot_hours'  => $row['slot_hours'],
                        'image'       => 'public/img/booked.png',
                    ]);

                } else {

                    $isCreating = true;

                    $planType = PlanType::create([
                        'library_id'  => $libraryId,
                        'branch_id'   => $branchId,
                        'day_type_id' => $row['day_type_id'],
                        'name'        => $planTypeName,
                        'start_time'  => $row['start_time'],
                        'end_time'    => $row['end_time'],
                        'slot_hours'  => $row['slot_hours'],
                        'image'       => 'public/img/booked.png',
                    ]);
                }

                $finalShiftIds[] = $planType->id;

                if ((int)$row['day_type_id'] == 11) {
                    $row['price'] = 0;
                }

                $existingPrice = PlanPrice::where('plan_type_id', $planType->id)
                    ->where('branch_id', $branchId)
                    ->first();

                if ($existingPrice) {

                    $existingPrice->update([
                        'price' => $row['price'],
                    ]);

                } else {

                    PlanPrice::create([
                        'library_id'   => $libraryId,
                        'branch_id'    => $branchId,
                        'plan_id'      => $plan->id,
                        'plan_type_id' => $planType->id,
                        'price'        => $row['price'],
                    ]);
                }
            }

            /* ================= DELETE REMOVED ================= */

            $existingShifts = PlanType::where('branch_id', $branchId)->get();

            foreach ($existingShifts as $planType) {

                if (!in_array($planType->id, $finalShiftIds)) {

                    $exists = LearnerDetail::where('plan_type_id', $planType->id)->exists();

                    if ($exists) {
                        throw new \Exception(
                            "Shift '{$planType->name}' cannot be deleted because learners are enrolled."
                        );
                    }

                    PlanPrice::where('plan_type_id', $planType->id)->forceDelete();
                    $planType->forceDelete();
                }
            }
            

            DB::commit();
            
            return [
                'status' => true,
                'message' => 'Library shifts saved successfully.',
                'setup' => ($isFirstTimeSetup && $isCreating && !$isUpdating) ? 'completed' : '' ,
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
