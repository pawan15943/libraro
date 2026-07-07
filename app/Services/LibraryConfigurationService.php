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
use App\Models\Scopes\LibraryScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class LibraryConfigurationService
{
   
    public function configure($request,array $validated,int $libraryId,$existingBranch,int $branchCount,$useTransaction = true)
    {
        
        
       if ($useTransaction) DB::beginTransaction();

        try {
          
        
            /* ======================
            HANDLE LOGO (WEB + APP)
            ====================== */

            $logoPath = $existingBranch->library_logo ?? null;

              // ✅ DELETE OLD FIRST
            if ($existingBranch && $existingBranch->library_logo && $logoPath !== $existingBranch->library_logo) {

                if (!str_contains($existingBranch->library_logo, 'upload/')) {
                    Storage::disk('public')->delete($existingBranch->library_logo);
                }
            }

            /* ========= CASE 1: WEB ========= */
            if ($request->hasFile('library_logo')) {

                $file = $request->file('library_logo');

                $fileName = "library_logo_" . time() . '.' . $file->getClientOriginalExtension();

                $destinationFolder = public_path('upload/library_logo');
                if (!File::exists($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0777, true);
                }
                $file->move($destinationFolder, $fileName);

                $logoPath = 'upload/library_logo/' . $fileName;
            }
            

            /* ========= CASE 2: APP ========= */
            elseif (!empty($validated['library_logo']) && is_string($validated['library_logo'])) {

                $logoPath = $this->moveTempFileToPublic($validated['library_logo'],'logo','upload/library_logo'); 
               
            }

          
            /* ========= FINAL ========= */
            $validated['library_logo'] = $logoPath;
          

           /* ======================
            HANDLE LIBRARY IMAGES (WEB + APP)
            ====================== */

            $images = [];

           

            /* ========= CASE 1: WEB ========= */
            if ($request->hasFile('library_images')) {

                foreach ($request->file('library_images') as $image) {

                    $fileName = 'img_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                    $destinationFolder = public_path('upload/library_images');
                    if (!File::exists($destinationFolder)) {
                        File::makeDirectory($destinationFolder, 0777, true);
                    }
                    $image->move($destinationFolder, $fileName);
                    $path = 'upload/library_images/' . $fileName;

                    $images[] = $path;
                }
            }

            /* ========= CASE 2: APP ========= */
            elseif (!empty($validated['library_images']) && is_array($validated['library_images'])) {

               $images= $validated['library_images'] = $this->moveTempFileToPublic( $validated['library_images'],'img','upload/library_images');
            }

            /* ========= FINAL ========= */
            $validated['library_images'] = !empty($images) ? $images : null;
           

           $validated['library_id'] = $libraryId;
           $validated['display_name'] = $validated['display_name'] ?? $validated['name'];

           $floorses = $validated['floors'] ?? [];
          
           $plans = $validated['plans'] ?? null;

            $hour  = $validated['hour'];
            $seats = $validated['seats'];
            unset($validated['hour'], $validated['seats']);

            if ($existingBranch) {
                $currentSeats = Hour::withoutGlobalScopes()
                    ->where('branch_id', $existingBranch->id)
                    ->value('seats');

                if ($currentSeats !== null && (int) $seats < (int) $currentSeats) {
                    throw new \Exception(
                        "Branch seats cannot be decreased. Current seats: {$currentSeats}"
                    );
                }
            }
           

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

            

            /* =========================
            CREATE BRANCH
            ========================= */
            $branchData = collect($validated)->except([
                'plans',
                'monthdays',
                'floors',
            ])->toArray();

            $branch = $existingBranch ?? new Branch();
       
            // $branch->fill($branchData);
            $clearableBranchFields = ['library_address', 'fixed_billing_date'];
            foreach ($branchData as $key => $value) {
                if (!is_null($value) || in_array($key, $clearableBranchFields, true)) {
                    $branch->$key = $value;
                }
            }
           
            $branch->library_id = $libraryId;
            if (!$existingBranch) {

               $baseSlug = Str::slug($validated['name'].'-'.$libraryId);

                $existingSlugs = Branch::where('slug', 'like', $baseSlug.'%')
                    ->pluck('slug')
                    ->toArray();

                $slug = $baseSlug;
                $count = 1;

                while (in_array($slug, $existingSlugs)) {
                    $slug = $baseSlug.'-'.$count++;
                }

                $branch->slug = $slug;
            }

          

            // ✅ THEN ASSIGN NEW
            if (!empty($validated['library_logo'])) {
                $branch->library_logo = $validated['library_logo'];
            }
            
            if (array_key_exists('library_images', $validated)) {
                $branch->library_images = $validated['library_images'];
            }

           
            if (array_key_exists('features', $validated)) {

                $features = $validated['features'];

                if (is_string($features)) {
                    $features = json_decode($features, true);
                }

                // if empty array → store null
                $branch->features = !empty($features) ? $features : null;
            }

            $branch->google_map = $validated['google_map'] ?? null;
            if (array_key_exists('working_days', $validated)) {
                $branch->working_days = $validated['working_days']; // allow null also
            }
                    
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
           
            if ($plans !== null && ($existingBranch || $branchCount == 0)) {
             
                        
                $existingPlans = Plan::where('library_id', $libraryId)
                    ->get()
                    ->keyBy('name');

               
                $incomingPlanNames = [];

                $baseMonthDays = null;
                foreach ($plans as $plan) {
                    [$num, $type] = explode(' ', $plan);

                    if ((int)$num === 1 && strtoupper($type) === 'MONTH') {
                        $baseMonthDays = $validated['monthdays'] ?? null;

                       
                    }
                }

                foreach ($plans as $plan) {

                    [$num, $type] = explode(' ', $plan);

                    $data = [
                        'library_id' => $libraryId,
                        'name'       => $plan,
                        'plan_id'    => (int)$num,
                        'type'       => strtoupper($type),
                        'monthdays'  => strtoupper($type) === 'MONTH' ? $baseMonthDays : null,
                    ];

                    if (isset($existingPlans[$plan])) {
                        
                        $existingPlans[$plan]->update($data);
                    } else {
                        $newPlan =Plan::create($data);
                        
                    }

                    $incomingPlanNames[] = $plan;
                }

                /* DELETE UNUSED PLANS */
                $deletedPlans = Plan::where('library_id', $libraryId)
                    ->whereNotIn('name', $incomingPlanNames)
                    ->delete();

                  
            }
           

            /* =========================
            FLOORS
            ========================= */
            $existingFloors = Floor::where('branch_id', $branch->id)
                ->get()
                ->keyBy('floor_no');

            $incomingFloorNos = [];

            foreach ($floors as $index => $floor) {
                $floorNo = $index + 1;
                $data = [
                    'branch_id' => $branch->id,
                    'name'      => $floor['name'],
                    'floor_no'  => $floorNo,
                    'from_seat' => (int)$floor['from'],
                    'to_seat'   => (int)$floor['to'],
                ];

                if (isset($existingFloors[$floorNo])) {
                    $existingFloors[$floorNo]->update($data);
                } else {
                    Floor::create($data);
                }

                $incomingFloorNos[] = $floorNo;
            }

            Floor::where('branch_id', $branch->id)
            ->whereNotIn('floor_no', $incomingFloorNos)
            ->delete();

           
         
            if ($existingBranch){
                $message='Branch updated successfully.';
            }else{
                $message='Branch added successfully.';
            }
             
            Library::where('id', $libraryId,)->update([
                'current_branch'=> $branch->id
            ]);

            if ($useTransaction) DB::commit();

            return [
                'status'   => true,
                'message'  => $message,
                'branch_id'=> $branch->id
            ];

        } catch (\Exception $e) {

            if ($useTransaction) DB::rollBack();

            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function shiftConfigure(array $validated,int $branchId,$useTransaction = true)
    {
        if ($useTransaction) DB::beginTransaction();

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
            

          $branchRecord = Hour::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->first();

       
            $existingPlanTypes = PlanType::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->get();
            $existingPlanTypesById = $existingPlanTypes->keyBy('id');

            $isFirstTimeSetup = $existingPlanTypes->count() === 0;

            $finalShiftIds = [];
            $coveredMinutes = [];
            $activeLearnerShiftIds = LearnerDetail::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('status', 1)
                ->whereIn('plan_type_id', $existingPlanTypes->pluck('id')->all())
                ->pluck('plan_type_id')
                ->flip();
           

            /* ================= GLOBAL COVERAGE CHECK ================= */
            $totalMinutes = 0;
            foreach ($validated['plan_types'] as $row) {

                $start = Carbon::parse($row['start_time']);
                $end   = Carbon::parse($row['end_time']);

                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }
                //  $totalMinutes += $start->diffInMinutes($end);

                while ($start < $end) {
                    $coveredMinutes[$start->format('H:i')] = true;
                    $start->addMinute();
                }
            }

            if (!$branchRecord) {
                throw new \Exception('Library operating hours are not configured for this branch.');
            }

            $operatingMinutes = (int) round(((float) $branchRecord->hour) * 60);
            $totalCoveredMinutes = count($coveredMinutes);

            if ((float) $branchRecord->hour !== 24.0 && $totalCoveredMinutes > $operatingMinutes) {
                throw new \Exception('Shift timing exceeds library hours.');
            }

            /* ================= CREATE / UPDATE ================= */

            $timePairs = [];
            $isCreating = false;
            $isUpdating = false;
            $planTypesss = $validated['plan_types'];

            foreach ($planTypesss as $index => $row) {
                $currentId = $row['plan_type_id'] ?? $row['id'] ?? null;
                $currentId = $currentId !== null && $currentId !== '' ? (int) $currentId : null;

                if ($currentId && !isset($existingPlanTypesById[$currentId])) {
                    throw new \Exception('Invalid shift selected.');
                }

                if (
                    (float) $branchRecord->hour !== 24.0
                    && (int) round(((float) $row['slot_hours']) * 60) > $operatingMinutes
                ) {
                    throw new \Exception('Selected hours exceed the library’s available hours.');
                }

                $start = Carbon::parse($row['start_time']);
                $end   = Carbon::parse($row['end_time']);

                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                $actualMinutes = $start->diffInMinutes($end);
                $submittedMinutes = (int) round(((float) $row['slot_hours']) * 60);
                $actualHours = $actualMinutes / 60;

                if ($submittedMinutes !== $actualMinutes) {
                    throw new \Exception(
                        "Slot duration must match shift time ({$actualHours} hours)."
                    );
                }

                $dayTypeId = (int) $row['day_type_id'];

                if (in_array($dayTypeId, [10, 11])) {
                    if ($actualMinutes !== $operatingMinutes) {
                        $shiftName = $dayTypeId == 11 ? 'VIP' : 'Reserved';
                        throw new \Exception(
                            "{$shiftName} shift must match library timing ({$branchRecord->hour} hours)."
                        );
                    }
                }

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

                /* ================= DB DUPLICATE CHECK ================= */
                $existing = null;
                if ($row['day_type_id'] == 0) {

                    $existing = PlanType::withoutGlobalScopes()
                        ->where('branch_id', $branchId)
                        ->where('day_type_id', 0)
                        ->where('start_time', $row['start_time'])
                        ->where('end_time', $row['end_time'])
                        ->first();
                   

                } else {

                    $existing = PlanType::withoutGlobalScopes()
                        ->where('branch_id', $branchId)
                        ->where('day_type_id', $row['day_type_id'])
                        ->first();
                }

                /* NEW CHECK: SAME NAME + SAME TIME */
                $duplicateNameTime = PlanType::withoutGlobalScopes()
                    ->where('branch_id', $branchId)
                    ->where('name', $row['custom_plan_type'])
                    ->where('start_time', $row['start_time'])
                    ->where('end_time', $row['end_time'])
                    ->first();

                if ($existing) {

                    if (!$currentId || $existing->id != $currentId) {
                        throw new \Exception(
                            $row['day_type_id'] == 0
                                ? 'Custom shift already exists for this time range.'
                                : 'This shift type already exists.'
                        );
                    }
                }
                /* NEW CHECK ADD*/
                if ($duplicateNameTime) {

                    if (!$currentId || $duplicateNameTime->id != $currentId) {
                        throw new \Exception(
                            'Shift with same name and time range already exists.'
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

                if ($shiftId && isset($existingPlanTypesById[$shiftId])) {

                    $isUpdating = true;

                    $planType = $existingPlanTypesById[$shiftId];

                    if (!$planType) {
                        throw new \Exception('Invalid shift selected.');
                    }

                    $timingChanged =
                        (int) $planType->day_type_id !== (int) $row['day_type_id']
                        || Carbon::parse($planType->start_time)->format('H:i') !== Carbon::parse($row['start_time'])->format('H:i')
                        || Carbon::parse($planType->end_time)->format('H:i') !== Carbon::parse($row['end_time'])->format('H:i')
                        || (float) $planType->slot_hours !== (float) $row['slot_hours'];

                    if (isset($activeLearnerShiftIds[$planType->id]) && $timingChanged) {
                        throw new \Exception(
                            "Shift '{$planType->name}' timing cannot be updated because active learners are enrolled."
                        );
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

               

                $existingPrice = PlanPrice::withoutGlobalScopes()
                    ->withTrashed()
                    ->where('library_id', $libraryId)
                    ->where('branch_id', $branchId)
                    ->where('plan_id', $plan->id)
                    ->where('plan_type_id', $planType->id)
                    ->first();

                if ($existingPrice) {
                    if ($existingPrice->trashed()) {
                        $existingPrice->restore();
                    }

                    $existingPrice->update([
                        'library_id'   => $libraryId,
                        'branch_id'    => $branchId,
                        'plan_id'      => $plan->id,
                        'plan_type_id' => $planType->id,
                        'price'        => $row['price'],
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

            $existingShifts = PlanType::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->get();

            foreach ($existingShifts as $planType) {

                if (!in_array($planType->id, $finalShiftIds)) {

                    if (isset($activeLearnerShiftIds[$planType->id])) {
                        throw new \Exception(
                            "Shift '{$planType->name}' cannot be deleted because active learners are enrolled."
                        );
                    }

                    PlanPrice::withoutGlobalScopes()
                        ->where('branch_id', $branchId)
                        ->where('plan_type_id', $planType->id)
                        ->forceDelete();
                    $planType->forceDelete();
                }
            }
            

            $this->markLibrarySetupCompletedIfReady($libraryId);

           if ($useTransaction) DB::commit();
            
            return [
                'status' => true,
                'message' => 'Library shifts saved successfully.',
                'setup' => ($isFirstTimeSetup && $isCreating && !$isUpdating) ? 'completed' : '' ,
            ];

        } catch (\Exception $e) {

            if ($useTransaction) DB::rollBack();

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function markLibrarySetupCompletedIfReady(int $libraryId): void
    {
        $hasBranch = Branch::where('library_id', $libraryId)
            ->where('status', 1)
            ->exists();

        $hasFloor = DB::table('floors')
            ->join('branches', 'branches.id', '=', 'floors.branch_id')
            ->where('branches.library_id', $libraryId)
            ->where('branches.status', 1)
            ->whereNull('branches.deleted_at')
            ->whereNull('floors.deleted_at')
            ->exists();

        $hasPlan = DB::table('plans')
            ->where('library_id', $libraryId)
            ->whereNull('deleted_at')
            ->exists();

        $hasPlanType = DB::table('plan_types')
            ->where('library_id', $libraryId)
            ->whereNull('deleted_at')
            ->exists();

        $hasPlanPrice = DB::table('plan_prices')
            ->where('library_id', $libraryId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasBranch && $hasFloor && $hasPlan && $hasPlanType && $hasPlanPrice) {
            Library::where('id', $libraryId)->update(['status' => 1]);
        }
    }

   function moveTempFileToPublic($fileInput, $filePrefix = 'file', $folder = 'upload/common')
    {
        $results = [];

        $files = is_array($fileInput) ? $fileInput : [$fileInput];

        foreach ($files as $file) {

            /* ========= CASE 1: FILE (WEB) ========= */
            if ($file instanceof \Illuminate\Http\UploadedFile) {

                $fileName = $filePrefix . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                $destinationFolder = public_path($folder);

                if (!File::exists($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0777, true);
                }

                $file->move($destinationFolder, $fileName);

                $results[] = $folder . '/' . $fileName;
            }

            /* ========= CASE 2: URL (APP) ========= */
            elseif (is_string($file)) {
                 if (str_contains($file, '/temp/')) {
                    // // EXACT SAME AS YOUR WORKING CODE
                    // $path = parse_url($file, PHP_URL_PATH);

                    // $pos = strpos($path, 'temp/');

                    // if ($pos === false) {
                    //     continue;
                    // }

                    // $tempPath = substr($path, $pos); // temp/abc.png
                    $path = parse_url($file, PHP_URL_PATH);

                    // ✅ normalize path first
                    if (str_contains($path, '/storage/')) {
                        $path = substr($path, strpos($path, '/storage/') + 9);
                    }

                    // now must be temp/xxx.png
                    if (!str_starts_with($path, 'temp/')) {
                        return null;
                    }

                    $tempPath = $path;

                    $sourcePath = storage_path('app/public/' . $tempPath);

                    if (File::exists($sourcePath)) {

                        $fileName = $filePrefix . '_' . time() . '_' . uniqid() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);

                        $destinationFolder = public_path($folder);

                        if (!File::exists($destinationFolder)) {
                            File::makeDirectory($destinationFolder, 0777, true);
                        }

                        $destinationPath = $destinationFolder . '/' . $fileName;

                        File::move($sourcePath, $destinationPath);

                        $results[] = $folder . '/' . $fileName;
                    }
                 }

                    // 🔥 CASE B: ALREADY PERMANENT → KEEP SAME
                elseif (str_contains($file, '/upload/')) {

                    $path = parse_url($file, PHP_URL_PATH);

                    $pos = strpos($path, 'upload/');

                    if ($pos !== false) {
                        $results[] = substr($path, $pos);
                    }
                }
                elseif (str_contains($file, '/uploads/')) {

                    $path = parse_url($file, PHP_URL_PATH);

                    $pos = strpos($path, 'uploads/');

                    if ($pos !== false) {
                        $results[] = substr($path, $pos);
                    }
                }
            }
        }

        // return same type
        return is_array($fileInput) ? $results : ($results[0] ?? null);
    }
}
