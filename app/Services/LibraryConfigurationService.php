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
           HANDLE LOGO UPLOAD
            ======================= */
           // Remove raw uploaded file from validated
           

            // if ($request->hasFile('library_logo')) {
            //     $validated['library_logo'] = $request->file('library_logo')
            //         ->store('uploads/logo', 'public');
            // }

           

            //  if (isset($validated['library_logo']) && $validated['library_logo'] instanceof \Illuminate\Http\UploadedFile) {
            //     unset($validated['library_logo']);
            // }
            /* ======================
            HANDLE LOGO (WEB + APP)
            ====================== */

            $logoPath = $existingBranch->library_logo ?? null;

              // ✅ DELETE OLD FIRST
            if ($existingBranch && $existingBranch->library_logo && $logoPath !== $existingBranch->library_logo) {

                if (!str_contains($existingBranch->library_logo, 'uploads/')) {
                    Storage::disk('public')->delete($existingBranch->library_logo);
                }
            }

            /* ========= CASE 1: WEB ========= */
            if ($request->hasFile('library_logo')) {

                $file = $request->file('library_logo');

                $fileName = "library_logo_" . time() . '.' . $file->getClientOriginalExtension();

                // your old logic (unchanged)
                $file->move(public_path('uploads'), $fileName);

                $logoPath = 'uploads/' . $fileName;
            }
            

            /* ========= CASE 2: APP ========= */
            elseif (!empty($validated['library_logo']) && is_string($validated['library_logo'])) {

                $validated['library_logo'] = $this->moveTempFileToPublic($validated['library_logo'],'logo','uploads/logo'); 
               
            }
            

            /* ========= FINAL ========= */
            $validated['library_logo'] = $logoPath;
          

           /* ======================
            HANDLE LIBRARY IMAGES (WEB + APP)
            ====================== */

            $images = [];

            /* ========= KEEP OLD (UPDATE CASE) ========= */
            if (!empty($existingBranch) && !empty($existingBranch->library_images)) {

                $images = is_array($existingBranch->library_images)
                    ? $existingBranch->library_images
                    : json_decode($existingBranch->library_images ?? '[]', true);
            }

            /* ========= CASE 1: WEB ========= */
            if ($request->hasFile('library_images')) {

                foreach ($request->file('library_images') as $image) {

                    $fileName = 'img_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                    $path = $image->storeAs('uploads/library_images', $fileName, 'public');

                    $images[] = $path;
                }
            }

            /* ========= CASE 2: APP ========= */
            elseif (!empty($validated['library_images']) && is_array($validated['library_images'])) {

                foreach ($validated['library_images'] as $fileUrl) {

                    // ✅ STEP 1: URL → path
                    $path = parse_url($fileUrl, PHP_URL_PATH);
                    // /libraryProject/storage/temp/abc.png

                    // ✅ STEP 2: extract only temp/...
                    $pos = strpos($path, 'temp/');

                    if ($pos === false) {
                        continue; // invalid
                    }

                    $tempPath = substr($path, $pos); // temp/abc.png

                    // ✅ STEP 3: source path (storage)
                    $sourcePath = storage_path('app/public/' . $tempPath);

                    if (File::exists($sourcePath)) {

                        // ✅ STEP 4: generate new filename
                        $fileName = 'img_' . time() . '_' . uniqid() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);

                        // ✅ STEP 5: destination folder (public)
                        $destinationFolder = public_path('uploads/library_images');

                        if (!File::exists($destinationFolder)) {
                            File::makeDirectory($destinationFolder, 0777, true);
                        }

                        // full destination path
                        $destinationPath = $destinationFolder . '/' . $fileName;

                        // ✅ STEP 6: move file
                        File::move($sourcePath, $destinationPath);

                        // ✅ STEP 7: store DB path
                        $images[] = 'uploads/library_images/' . $fileName;
                    }
                }
            }

            /* ========= FINAL ========= */
            $validated['library_images'] = !empty($images) ? $images : null;
           

            // if ($request->hasFile('library_images')) {

            //     $images = [];

            //     // keep old images in edit mode
            //     if (!empty($existingBranch) && !empty($existingBranch->library_images)) {

            //         $existingImages = $existingBranch->library_images;


            //         $images = is_array($existingImages) ? $existingImages : json_decode($existingImages ?? '[]', true);
            //     }

            //     foreach ($request->file('library_images') as $image) {
            //         $images[] = $image->store('uploads/library_images', 'public');
            //     }

            //     $validated['library_images'] = json_encode($images);

            // } else {

            //     if (!empty($existingBranch) && !empty($existingBranch->library_images)) {
            //         $validated['library_images'] = is_array($existingBranch->library_images) ? json_encode($existingBranch->library_images) : $existingBranch->library_images;
            //     } else {
            //         $validated['library_images'] = null;
            //     }
            // }


           $validated['library_id'] = $libraryId;
           $validated['display_name'] = $validated['display_name'] ?? $validated['name'];

           $floorses = $validated['floors'] ?? [];
          
           $plans = $request->has('plan') 
            ? ($validated['plans'] ?? []) 
            : null;

            $hour  = $validated['hour'];
            $seats = $validated['seats'];
            unset($validated['hour'], $validated['seats']);
           

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
            foreach ($branchData as $key => $value) {
                if (!is_null($value)) {
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
            
            

            // if (!empty($validated['features'])) {

            //     $features = $validated['features'];

            //     if (is_string($features)) {
            //         $features = json_decode($features, true);
            //     }

            //     $branch->features = $features; 
            // }
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
                        Plan::create($data);
                    }

                    $incomingPlanNames[] = $plan;
                }

                /* DELETE UNUSED PLANS */
                Plan::where('library_id', $libraryId)
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

       
            $existingPlanTypes = PlanType::where('branch_id', $branchId)->get();
            $existingPlanTypesById = $existingPlanTypes->keyBy('id');

            $isFirstTimeSetup = $existingPlanTypes->count() === 0;

            $finalShiftIds = [];
            $coveredMinutes = [];
           

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

            $totalCoveredHours = count($coveredMinutes) / 60;
            // $totalCoveredHours = $totalMinutes / 60;

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
                $existing = null;
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

                if ($shiftId && isset($existingPlanTypesById[$shiftId])) {

                    $isUpdating = true;

                    $planType = $existingPlanTypesById[$shiftId];

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

               

                // $existingPrice = PlanPrice::where('plan_type_id', $planType->id)
                //     ->where('branch_id', $branchId)
                //     ->first();


                 $existingPrices = PlanPrice::where('branch_id', $branchId)->get()->keyBy('plan_type_id');
                $existingPrice = $existingPrices[$planType->id] ?? null;

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

    function moveTempFileToPublic($fileInput, $filePrefix = 'file', $folder = 'uploads/common')
    {
        $results = [];

        // normalize to array
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

                $path = parse_url($file, PHP_URL_PATH);

                $pos = strpos($path, 'temp/');

                if ($pos === false) continue;

                $tempPath = substr($path, $pos);

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
        }

        // ✅ return single or array automatically
        if (!is_array($fileInput)) {
            return $results[0] ?? null;
        }

        return $results;
    }
}
