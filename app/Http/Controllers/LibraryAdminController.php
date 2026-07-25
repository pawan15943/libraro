<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\LearnerDetail;
use App\Models\Library;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

// Superadmin-side management of a library's branches (and their Plans, Plan Types,
// Plan Prices, Seats/Hours, and profile edit) - reached from the Library List's
// "Branches" action. Split out of LibraryController to keep this self-contained
// feature separate from that controller's tenant-facing library CRUD.
class LibraryAdminController extends Controller
{
    public function branches($id)
    {
        $library = Library::findOrFail($id);

        $branches = Branch::withoutGlobalScopes()
            ->where('library_id', $id)
            ->orderBy('id')
            ->get();

        return view('administrator.library-branches', compact('library', 'branches'));
    }

    private function resolveBranchForAdmin($branchId)
    {
        return Branch::withoutGlobalScopes()->findOrFail($branchId);
    }

    private function branchHasActiveLearnersForPlanType($branchId, $planTypeId): bool
    {
        return LearnerDetail::withoutGlobalScopes()
            ->where('status', 1)
            ->where('branch_id', $branchId)
            ->where('plan_type_id', $planTypeId)
            ->exists();
    }

    // Plan is library-wide (shared across branches), matching the tenant-side planView() behaviour.
    public function branchPlans($branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        // Named branchPlans (not "plans"): AppServiceProvider registers a global
        // View::composer('*', ...) that unconditionally injects a $plans variable
        // (scoped by getLibraryId(), always null for the superadmin/web guard) into
        // every view, silently overwriting a same-named variable set here.
        $branchPlans = Plan::withoutGlobalScopes()
            ->withTrashed()
            ->where('library_id', $branch->library_id)
            ->orderByDesc('id')
            ->get();

        return view('administrator.branch-plans', compact('branch', 'branchPlans'));
    }

    public function saveBranchPlan(Request $request, $branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $validated = $request->validate([
            'id' => [
                'nullable',
                Rule::exists('plans', 'id')->where(fn ($q) => $q->where('library_id', $branch->library_id)),
            ],
            'type' => 'required|in:MONTH,YEAR,DAY,WEEK',
            'plan_id' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('plans', 'plan_id')
                    ->where(fn ($q) => $q->where('library_id', $branch->library_id)->where('type', $request->type))
                    ->ignore($request->id),
            ],
            'monthdays' => 'nullable|in:28,30',
        ]);

        $data = [
            'library_id' => $branch->library_id,
            'type' => $validated['type'],
            'plan_id' => $validated['plan_id'],
            'monthdays' => $validated['monthdays'] ?? null,
            'name' => $validated['plan_id'] . ' ' . $validated['type'],
        ];

        if (!empty($validated['id'])) {
            $plan = Plan::withoutGlobalScopes()->withTrashed()
                ->where('library_id', $branch->library_id)
                ->findOrFail($validated['id']);
            $plan->update($data);
        } else {
            Plan::create($data);
        }

        return redirect()->route('library.branch.plans', $branchId)->with('success', 'Plan saved successfully.');
    }

    public function deleteBranchPlan($planId)
    {
        Plan::withoutGlobalScopes()->withTrashed()->findOrFail($planId)->delete();

        return response()->json(['status' => true, 'message' => 'Plan deleted successfully.']);
    }

    public function branchPlanTypes($branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $monthlyPlan = Plan::withoutGlobalScopes()
            ->where('library_id', $branch->library_id)
            ->where('plan_id', 1)
            ->where('type', 'MONTH')
            ->first();

        // Named branchPlanTypes (not "planTypes"): AppServiceProvider registers a
        // global View::composer('*', ...) that unconditionally injects a $planTypes
        // variable (scoped by getLibraryId(), always null for the superadmin/web
        // guard) into every view, silently overwriting a same-named variable here.
        $branchPlanTypesQuery = PlanType::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->select('plan_types.*')
            ->selectSub(function ($query) use ($branchId) {
                $query->from('learner_detail')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('learner_detail.plan_type_id', 'plan_types.id')
                    ->where('learner_detail.status', 1)
                    ->where('learner_detail.branch_id', $branchId);
            }, 'active_learners_count')
            ->orderBy('id');

        if ($monthlyPlan) {
            // PlanPrice has its own global 'branch' scope keyed off getCurrentBranch(),
            // which is null for the superadmin/web guard - must bypass it here too.
            $branchPlanTypesQuery->with(['price' => fn ($query) => $query->withoutGlobalScopes()
                ->where('plan_id', $monthlyPlan->id)]);
        }

        $branchPlanTypes = $branchPlanTypesQuery->get();
        $operatingHour = DB::table('hour')->where('branch_id', $branchId)->first();

        return view('administrator.branch-plan-types', compact('branch', 'branchPlanTypes', 'monthlyPlan', 'operatingHour'));
    }

    private const DAY_TYPE_NAMES = [
        1 => 'Full Day',
        2 => 'First Half',
        3 => 'Second Half',
        8 => 'All Day',
        9 => 'Full Night',
        10 => 'Reserved',
        11 => 'VIP',
    ];

    // Mirrors the tenant onboarding wizard (LibraryConfigurationService::shiftConfigure()):
    // saves a whole batch of shifts in one submit, each shift's PlanType + PlanPrice created/
    // updated together, and any existing PlanType left out of the submission gets removed -
    // but reimplemented here scoped by an explicit $branchId rather than session state, since
    // that service resolves branch/library from getCurrentBranch()/getLibraryId() (null for
    // the superadmin/web guard).
    public function saveBranchPlanTypes(Request $request, $branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $validated = $request->validate([
            'plan_types' => 'required|array|min:1',
            'plan_types.*.plan_type_id' => 'nullable|integer',
            'plan_types.*.day_type_id' => 'required',
            'plan_types.*.custom_plan_type' => 'nullable|string|max:100',
            'plan_types.*.start_time' => 'required|date_format:H:i',
            'plan_types.*.end_time' => 'required|date_format:H:i',
            'plan_types.*.slot_hours' => 'required|numeric|min:1',
            'plan_types.*.price' => 'required|numeric|min:0',
        ]);

        foreach ($validated['plan_types'] as $index => $row) {
            if ((string) $row['day_type_id'] === '0' && empty($row['custom_plan_type'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Custom plan type name is required.',
                    'errors' => ["plan_types.$index.custom_plan_type" => ['Custom plan type name is required.']],
                ], 422);
            }
        }

        $monthlyPlan = Plan::withoutGlobalScopes()
            ->where('library_id', $branch->library_id)
            ->where('plan_id', 1)
            ->where('type', 'MONTH')
            ->first();

        if (!$monthlyPlan) {
            return response()->json([
                'status' => false,
                'message' => 'This library has no "1 Month" plan yet - add one on the Plans page before setting shift prices.',
            ], 422);
        }

        $operatingHour = DB::table('hour')->where('branch_id', $branchId)->first();

        DB::beginTransaction();
        try {
            $survivedIds = [];

            foreach ($validated['plan_types'] as $row) {
                $dayTypeId = (int) $row['day_type_id'];

                if (in_array($dayTypeId, [10, 11], true) && $operatingHour && $row['slot_hours'] != $operatingHour->hour) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Reserved and VIP shifts must span the full operating hours (' . $operatingHour->hour . ').',
                    ], 422);
                }

                $existingId = $row['plan_type_id'] ?? null;

                if ($existingId && $this->branchHasActiveLearnersForPlanType($branchId, $existingId)) {
                    // Active-learner shifts are read-only in the form (disabled inputs), so
                    // just carry the existing row forward untouched instead of erroring.
                    $survivedIds[] = (int) $existingId;
                    continue;
                }

                $name = $dayTypeId === 0 ? $row['custom_plan_type'] : (self::DAY_TYPE_NAMES[$dayTypeId] ?? $row['custom_plan_type']);
                $price = $dayTypeId === 11 ? 0 : $row['price'];

                $data = [
                    'library_id' => $branch->library_id,
                    'branch_id' => $branchId,
                    'day_type_id' => $dayTypeId,
                    'name' => $name,
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'slot_hours' => $row['slot_hours'],
                    'image' => 'public/img/booked.png',
                ];

                $planType = $existingId
                    ? PlanType::withoutGlobalScopes()->withTrashed()->where('branch_id', $branchId)->find($existingId)
                    : null;

                if ($planType) {
                    $planType->update($data);
                } else {
                    $planType = PlanType::create($data);
                }

                $survivedIds[] = $planType->id;

                $priceRow = PlanPrice::withoutGlobalScopes()->withTrashed()
                    ->where('branch_id', $branchId)
                    ->where('plan_id', $monthlyPlan->id)
                    ->where('plan_type_id', $planType->id)
                    ->first();

                if ($priceRow) {
                    if ($priceRow->trashed()) {
                        $priceRow->restore();
                    }
                    $priceRow->update(['price' => $price]);
                } else {
                    PlanPrice::create([
                        'library_id' => $branch->library_id,
                        'branch_id' => $branchId,
                        'plan_id' => $monthlyPlan->id,
                        'plan_type_id' => $planType->id,
                        'price' => $price,
                    ]);
                }
            }

            $toRemove = PlanType::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->whereNotIn('id', $survivedIds)
                ->get();

            foreach ($toRemove as $planType) {
                if ($this->branchHasActiveLearnersForPlanType($branchId, $planType->id)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => "Cannot remove \"{$planType->name}\" - active learners are assigned to it.",
                    ], 422);
                }

                PlanPrice::withoutGlobalScopes()->where('plan_type_id', $planType->id)->delete();
                $planType->delete();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to save shifts: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Shifts saved successfully.',
            'redirect' => route('library.branch.plantypes', $branchId),
        ]);
    }

    public function deleteBranchPlanType($planTypeId)
    {
        $planType = PlanType::withoutGlobalScopes()->withTrashed()->findOrFail($planTypeId);

        if ($this->branchHasActiveLearnersForPlanType($planType->branch_id, $planType->id)) {
            return response()->json([
                'status' => false,
                'message' => 'This plan type cannot be deleted because active learners are assigned to it.',
            ], 422);
        }

        PlanPrice::withoutGlobalScopes()->where('plan_type_id', $planType->id)->delete();
        $planType->delete();

        return response()->json(['status' => true, 'message' => 'Plan type deleted successfully.']);
    }

    public function branchPrices($branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $prices = PlanPrice::withoutGlobalScopes()
            ->withTrashed()
            ->where('branch_id', $branchId)
            ->with([
                'plan' => fn ($query) => $query->withoutGlobalScopes()->withTrashed(),
                'planType' => fn ($query) => $query->withoutGlobalScopes()->withTrashed(),
            ])
            ->orderByDesc('id')
            ->get();

        // branchPlansForSelect/branchPlanTypesForSelect (not "plans"/"planTypes"): see the
        // note in branchPlans() - AppServiceProvider's global view composer clobbers those names.
        $branchPlansForSelect = Plan::withoutGlobalScopes()->where('library_id', $branch->library_id)->get();
        $branchPlanTypesForSelect = PlanType::withoutGlobalScopes()->where('branch_id', $branchId)->get();

        return view('administrator.branch-prices', compact('branch', 'prices', 'branchPlansForSelect', 'branchPlanTypesForSelect'));
    }

    public function saveBranchPrice(Request $request, $branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $validated = $request->validate([
            'id' => 'nullable|integer',
            'plan_id' => 'required|integer',
            'plan_type_id' => 'required|integer',
            'price' => 'required|numeric|min:0',
        ]);

        $data = [
            'library_id' => $branch->library_id,
            'branch_id' => $branchId,
            'plan_id' => $validated['plan_id'],
            'plan_type_id' => $validated['plan_type_id'],
            'price' => $validated['price'],
        ];

        if (!empty($validated['id'])) {
            $price = PlanPrice::withoutGlobalScopes()->withTrashed()
                ->where('branch_id', $branchId)
                ->findOrFail($validated['id']);
            $price->update($data);
        } else {
            PlanPrice::create($data);
        }

        return redirect()->route('library.branch.prices', $branchId)->with('success', 'Plan price saved successfully.');
    }

    public function deleteBranchPrice($priceId)
    {
        PlanPrice::withoutGlobalScopes()->withTrashed()->findOrFail($priceId)->delete();

        return response()->json(['status' => true, 'message' => 'Plan price deleted successfully.']);
    }

    // getBranchShiftTiming() isn't safe to reuse here: it queries PlanType without
    // withoutGlobalScopes(), and LibraryScope filters by getLibraryId(), which is
    // null for the web/superadmin guard - so it would silently return 0 for every
    // branch instead of throwing. This inlines the same min/max shift-time logic,
    // scoped explicitly by branch_id.
    private function branchShiftTimingForAdmin($branchId): float
    {
        $planTypes = PlanType::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();

        if ($planTypes->isEmpty()) {
            return 0;
        }

        $minStart = null;
        $maxEnd = null;

        foreach ($planTypes as $planType) {
            $start = Carbon::parse($planType->start_time);
            $end = Carbon::parse($planType->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $minStart = $minStart ? min($minStart, $start) : $start;
            $maxEnd = $maxEnd ? max($maxEnd, $end) : $end;
        }

        return round($minStart->diffInMinutes($maxEnd) / 60, 2);
    }

    public function branchSeatHour($branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);
        $hourRow = DB::table('hour')->where('branch_id', $branchId)->first();

        return view('administrator.branch-seat-hour', compact('branch', 'hourRow'));
    }

    public function saveBranchSeatHour(Request $request, $branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $validated = $request->validate([
            'seats' => 'required|integer|min:1',
            'hour' => 'required|integer|min:1|max:24',
        ]);

        $hourRow = DB::table('hour')->where('branch_id', $branchId)->first();

        if (!$hourRow) {
            return back()->with('error', 'No operating-hour record exists yet for this branch.')->withInput();
        }

        $existingSeats = $hourRow->seats;
        if ($existingSeats !== null && $existingSeats > $validated['seats']) {
            return back()->with('error', 'Seat count can only be increased, not decreased, once set.')->withInput();
        }

        $shiftTiming = $this->branchShiftTimingForAdmin($branchId);
        if ($shiftTiming > 0 && $validated['hour'] < $shiftTiming) {
            return back()->with('error', 'Operating hours cannot be less than the branch\'s configured shift hours (' . $shiftTiming . ').')->withInput();
        }

        DB::table('hour')->where('branch_id', $branchId)->update([
            'seats' => $validated['seats'],
            'hour' => $validated['hour'],
            'updated_at' => now(),
        ]);

        return redirect()->route('library.branch.seatHour', $branchId)->with('success', 'Seats and operating hours updated successfully.');
    }

    public function editBranch($branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);
        $states = State::where('is_active', 1)->get();
        $cities = City::where('is_active', 1)->where('state_id', $branch->state_id)->get();
        $features = DB::table('features')->whereNull('deleted_at')->get();

        return view('administrator.branch-edit', compact('branch', 'states', 'cities', 'features'));
    }

    public function updateBranch(Request $request, $branchId)
    {
        $branch = $this->resolveBranchForAdmin($branchId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'library_category' => 'nullable|string',
            'working_days' => 'nullable|string',
            'mobile' => 'required|string|max:10',
            'email' => 'required|email',
            'library_address' => 'required|string',
            'library_zip' => 'required|string|max:6',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'library_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'features' => 'nullable|array',
            'features.*' => 'integer',
            'google_map' => 'nullable|string',
            'description' => 'nullable|string',
            'upi_id' => 'nullable|string',
            'founder_day' => 'nullable|date',
            'fixed_billing_date' => 'nullable|integer|min:1|max:31',
            'longitude' => 'nullable|string',
            'latitude' => 'nullable|string',
            'extend_days' => 'nullable|integer|min:0|max:30',
            'locker_amount' => 'nullable|integer|min:0',
            'token_money' => 'nullable|integer|min:0',
            'library_images' => 'nullable|array|max:4',
            'library_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'deleted_images' => 'nullable|array',
        ]);

        if ($request->hasFile('library_images')) {
            $uploadedFiles = [];
            foreach ($request->file('library_images') as $file) {
                $newName = 'library_img_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $destinationFolder = public_path('upload/library_images');
                if (!File::exists($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0777, true);
                }
                $file->move($destinationFolder, $newName);
                $uploadedFiles[] = 'upload/library_images/' . $newName;
            }
        } else {
            $uploadedFiles = [];
        }

        // library_images/features are cast to array on the Branch model, so $branch->library_images
        // is already a decoded array here - and the array we assign back is auto-encoded on save.
        $existingImages = $branch->library_images ?? [];
        $deletedImages = $request->input('deleted_images', []);
        $remainingImages = array_diff($existingImages, $deletedImages);
        $finalImages = array_values(array_merge($remainingImages, $uploadedFiles));

        if (!empty($finalImages)) {
            $validated['library_images'] = $finalImages;
        } else {
            unset($validated['library_images']);
        }

        if ($request->hasFile('library_logo')) {
            $logo = $request->file('library_logo');
            $newName = 'library_logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $destinationFolder = public_path('upload/library_logo');
            if (!File::exists($destinationFolder)) {
                File::makeDirectory($destinationFolder, 0777, true);
            }
            $logo->move($destinationFolder, $newName);
            $validated['library_logo'] = 'upload/library_logo/' . $newName;
        }

        $validated['is_profile'] = (($request->longitude && $request->latitude) || $request->google_map) ? 1 : 0;
        $validated['features'] = !empty($validated['features']) ? $validated['features'] : null;

        $branch->update($validated);

        return redirect()->route('library.branches', $branch->library_id)->with('success', 'Branch profile updated successfully.');
    }
}
