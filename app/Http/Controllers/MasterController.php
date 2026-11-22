<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Expense;
use App\Models\Feature;
use App\Models\Feedback;
use App\Models\Hour;
use App\Models\Inquiry;
use App\Models\Learner;
use App\Models\Library;
use App\Models\PermissionCategory;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\LearnerDetail;
use Illuminate\Http\Request;
use App\Models\Permission;
use Exception;
use DB;
use Yajra\DataTables\Facades\DataTables;
use Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use App\Models\Exam;

class MasterController extends Controller
{
    public const PERMISSION_MANAGE_FEATURE_TOGGLES = 'Manage Feature Toggles';

    /**
     * Models the library-side generic master endpoints (master.store / master.edit /
     * master.delete) may touch. Those endpoints take the model name from the request, so
     * without this list a library could read, update or delete any table (other
     * libraries, admin users...).
     */
    private const LIBRARY_MASTER_MODELS = [
        'Plan', 'PlanType', 'PlanPrice', 'Hour', 'Branch', 'Expense', 'Exam', 'Floor', 'BooksCategory', 'LibraryUser',
    ];


    public function stateWiseCity(Request $request)
    {

        if ($request->state_id) {
            $stateId = $request->state_id;
            $city = City::where('state_id', $stateId)->pluck('city_name', 'id');

            return response()->json($city);
        }
    }
    public function index()
    {
        // Plan permissions are now set on the plan form (its assign routes were removed).
        return redirect()->route('admin.subscriptions');
    }

    public function showPlanwisePermission($id){
        return redirect()->route('subscriptions.edit', $id);
    }
    public function subscriptionMaster(){
        $subscriptions = Subscription::withTrashed()->get();
        $permissions = Permission::where('guard_name','library')->get();
        $subscription=null;
        $users = User::all();
        return view('master.subscriptionMaster', compact('subscriptions', 'permissions', 'users','subscription'));
    }
    public function storeSubscription(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'monthly_fees' => 'required|numeric|min:0',
            'yearly_fees' => 'nullable|numeric|min:0',
            'max_seats' => 'nullable|integer|min:0',
            'max_branches' => 'nullable|integer|min:0',
        ]);
        Subscription::create($validated);
        return redirect()->back()->with('success', 'Subscription created successfully');
    }
    public function subscriptionMasterUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'monthly_fees' => 'required|numeric|min:0',
            'yearly_fees' => 'nullable|numeric|min:0',
            'max_seats' => 'nullable|integer|min:0',
            'max_branches' => 'nullable|integer|min:0',
        ]);

        $subscription = Subscription::findOrFail($id);
        $subscription->update($validated);

        return redirect()->route('subscription.master')->with('success', 'Subscription updated successfully.');
    }
    public function subscriptionMasterEdit($id = null){
        $subscription = $id ? Subscription::withTrashed()->find($id) : null;
        $subscriptions = Subscription::withTrashed()->get();
        $permissions = Permission::where('guard_name','library')->get();
       
        $users = User::all();
        return view('master.subscriptionMaster', compact('subscriptions', 'permissions', 'users','subscription'));
    }
    public function deactiveSubscription($id)
    {
        
        try {
            DB::transaction(function () use ($id) {
                
                Subscription::where('id', $id)->delete();
            });
    
           
            return response()->json(['success' => 'Subscriptions successfully.']);
        } catch (\Exception $e) {
           
            return response()->json(['error' => 'An error occurred while deleting the customer: ' . $e->getMessage()], 500);
        }
    
        return response()->json(['success' => 'Learner deleted successfully.']);
    }
    // ==========================================
    // PERMISSION CATEGORIES SEPARATE CRUD
    // ==========================================

    public function permissionCategoriesIndex(Request $request)
    {
        $query = PermissionCategory::withCount('permissions');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $categories = $query->orderBy('id', 'desc')->paginate(15);
        return view('master.permission_categories.index', compact('categories'));
    }

    public function permissionCategoryCreate()
    {
        return view('master.permission_categories.form');
    }

    public function permissionCategoryEdit($id)
    {
        $category = PermissionCategory::findOrFail($id);
        return view('master.permission_categories.form', compact('category'));
    }

    public function storeOrUpdateCategory(Request $request, $categoryId = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permission_categories,name,' . $categoryId,
        ]);

        PermissionCategory::updateOrCreate(['id' => $categoryId], $data);

        return redirect()->route('permission-categories.index')->with('success', 'Permission Category saved successfully.');
    }

    public function deleteCategory($categoryId)
    {
        $category = PermissionCategory::findOrFail($categoryId);
        Permission::where('permission_category_id', $categoryId)->update(['permission_category_id' => null]);
        $category->delete();

        return redirect()->route('permission-categories.index')->with('success', 'Permission Category deleted successfully.');
    }

    // ==========================================
    // PERMISSIONS SEPARATE CRUD & SEPARATE PAGES
    // ==========================================

    public function managePermissions(Request $request)
    {
        $query = Permission::where('guard_name', 'library')->with('category');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $permissions = $query->orderBy('id', 'desc')->get();
        $categories = PermissionCategory::all();
        return view('master.permissions.index', compact('permissions', 'categories'));
    }

    public function permissionCreate()
    {
        $categories = PermissionCategory::all();
        return view('master.permissions.form', compact('categories'));
    }

    public function permissionEdit($id)
    {
        $permission = Permission::findOrFail($id);
        $categories = PermissionCategory::all();
        return view('master.permissions.form', compact('permission', 'categories'));
    }

    public function storeOrUpdatePermission(Request $request, $permissionId = null)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'description'            => 'nullable|string',
            'permission_category_id' => 'required|exists:permission_categories,id',
            'slug'                   => 'nullable|string',
            'guard_name'             => 'required|string',
        ]);

        $slug = $request->slug ?? \Illuminate\Support\Str::slug($request->name);

        if ($permissionId) {
            $permission = Permission::findOrFail($permissionId);
            $permission->update([
                'name'                   => $request->name,
                'description'            => $request->description,
                'permission_category_id' => $request->permission_category_id,
                'guard_name'             => $request->guard_name,
                'slug'                   => $slug,
            ]);
            $message = 'Permission updated successfully.';
        } else {
            $exists = Permission::where('name', $request->name)->where('guard_name', $request->guard_name)->exists();
            if ($exists) {
                return redirect()->route('permissions')->with('warning', $request->name . ' Permission already exists.');
            }
            Permission::create([
                'name'                   => $request->name,
                'description'            => $request->description,
                'permission_category_id' => $request->permission_category_id,
                'guard_name'             => $request->guard_name,
                'slug'                   => $slug,
            ]);
            $message = 'Permission created successfully.';
        }

        return redirect()->route('permissions')->with('success', $message);
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();

        return redirect()->route('permissions')->with('success', 'Permission deleted successfully.');
    }
    public function deleteSubscriptionPermission(Request $request, $permissionId)
    {
        $subscriptionId = $request->subscription_id;
    
      
        DB::table('subscription_permission')
            ->where('subscription_id', $subscriptionId)
            ->where('permission_id', $permissionId)
            ->delete();
    
        return redirect()->back()->with('success', 'Permission successfully deleted from the subscription.');
    }
    
    public function getPermissions($id)
    {
        $subscription = Subscription::with('permissions')->find($id);

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $permissions = $subscription->permissions->pluck('id')->toArray();
  
        return response()->json(['permissions' => $permissions]);
    }

    public function assignPermissionsToSubscription(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required',
            'permissions' => 'array', 
        ]);

        $subscription = Subscription::find($request->subscription_id);

        if (!$subscription) {
            return redirect()->back()->withErrors('Subscription not found');
        }

        $subscription->permissions()->sync($request->permissions);
          // $subscription = Subscription::findOrFail($request->subscription_id);
            // $subscription->permissions()->attach($permission->id);
        return redirect()->back()->with('success', 'Permissions assigned/updated successfully.');
    }

    

    public function masterPlan(Request $request){
         $plans=Plan::where('library_id',getLibraryId())->withTrashed()->listable()->get();
            
            $plantype=PlanType::withTrashed()->where('library_id',getLibraryId())->listable()->get();
            $plantypes=PlanType::where('library_id',getLibraryId())->listable()->get();
           
           
            $seat_button=Library::where('id',getLibraryId())->where('status',1)->exists();
            
            
            $notleaner=Learner::where('id',getAuthenticatedUser()->id)->count();
        if(getCurrentBranch()==0){
               $hours=Hour::where('library_id',getLibraryId())->withTrashed()->get();
               $is_extendday=Branch::where('library_id',getLibraryId())->whereNotNull('extend_days')->exists();
               $branches=Branch::where('library_id',getLibraryId())->get();
                $planprice=PlanPrice::where('library_id',getLibraryId())->withTrashed()->whereNotIn('plan_id', Plan::withoutGlobalScopes()->where('is_custom', 1)->select('id'))->with(['plan', 'planType'])->get();
                $expenses=Expense::get();
        }else{
            $hours=Hour::where('library_id',getLibraryId())->where('branch_id',getCurrentBranch())->withTrashed()->get();
            $is_extendday=Branch::where('library_id',getLibraryId())->where('id',getCurrentBranch())->whereNotNull('extend_days')->exists();
            $branches=Branch::where('library_id',getLibraryId())->where('id',getCurrentBranch())->get();
             $planprice=PlanPrice::where('library_id',getLibraryId())->where('branch_id',getCurrentBranch())->withTrashed()->whereNotIn('plan_id', Plan::withoutGlobalScopes()->where('is_custom', 1)->select('id'))->with(['plan', 'planType'])->get();
             $expenses=Expense::get();
        }

       
        return view('master.library-masters',compact('branches','plans','hours','plantype','planprice','plantypes','seat_button','expenses','is_extendday','notleaner'));
    }
    
    public function storemaster(Request $request, $id = null)
    {
        $modelClass = $this->resolveMasterModel($request->databasemodel);
        if (!$modelClass) {
            return response()->json(['error' => true, 'message' => 'Invalid master type.'], 422);
        }

        // Tenant ids come from the session, never from the posted form.
        if ($request->has('library_id')) {
            $request->merge(['library_id' => getLibraryId()]);
        }
        if ($request->filled('branch_id') && !$this->ownsBranch($request->branch_id)) {
            return response()->json(['error' => true, 'message' => 'You are not allowed to change this branch.'], 403);
        }

        $this->validationfunction($request);
        $table=$request->databasetable;
        $data=$request->all();
        $plan_type_name=null;
        $branchRecord = Hour::where('branch_id', getCurrentBranch())->first();

        if (in_array($request->databasemodel, ['PlanType', 'Floor'], true) && !$branchRecord) {
            return response()->json([
                'error' => true,
                'message' => 'Please set the operating hours and total seats for this branch first.'
            ], 422);
        }
        if (in_array($request->databasemodel, ['Plan', 'PlanType'], true)
            && $request->filled('id')
            && $this->hasActiveLearnersForMaster($request->databasemodel, (int) $request->id)) {
            return response()->json([
                'error' => true,
                'message' => $request->databasemodel === 'Plan'
                    ? 'This plan cannot be edited because active learners are assigned to it.'
                    : 'This plan type cannot be edited because active learners are assigned to it.'
            ], 422);
        }

        if ($request->databasemodel == 'Plan'){
            $data['name']=$request->plan_id .' '.$request->type;
            
        }
        if ($request->databasemodel == 'PlanType'){

           $modeError = $this->normalizeShiftBookingMode($request);
           if ($modeError) {
                return response()->json(['error' => true, 'message' => $modeError]);
           }

           if ($request->slot_hours > $branchRecord->hour) {
                return response()->json([
                    'error' => true,
                    'message' => 'Selected hours exceed the library’s available hours.'
                ]);
            }

            // the shift runs inside the library's open – close time
            $libraryWindow = \App\Services\BranchHoursService::windowOf((int) $branchRecord->hour, $branchRecord->open_time);
            $span = \App\Services\BranchHoursService::span($request->start_time, $request->end_time);
            if ($libraryWindow && $span && !\App\Services\BranchHoursService::fits($libraryWindow, $span[0], $span[1])) {
                return response()->json([
                    'error' => true,
                    'message' => 'This shift is outside library hours (' . \App\Services\BranchHoursService::windowLabel($libraryWindow) . '). Pick a time inside them.'
                ]);
            }
            
            // $minTime = PlanType::min('start_time');
            // $maxTime = PlanType::max('end_time');

            // $start = Carbon::parse($request->start_time);
            // $end   = Carbon::parse($request->end_time);

            // $globalMin = Carbon::parse($minTime);
            // $globalMax = Carbon::parse($maxTime);

            // if ($end->lessThanOrEqualTo($globalMin)) {
            //     $end->addDay();
            // }
            // $totalHours = $globalMin->diffInHours($end);

            // // 1. Check within allowed time range
            // if (PlanType::count() > 0 && $totalHours > $branchRecord->hour) {
            //      return response()->json([
            //         'error' => true,
            //         'message' => 'You can’t add shift timings outside the library’s hours. Please check and adjust your shift time.'
            //     ]);
            // }
            // any-time advanced shifts don't tie up hours of the day, so they don't count here
            $minTime = PlanType::where('branch_id', getCurrentBranch())
                ->where('id', '!=', $data['id'])
                ->withTimeWindow()
                ->min('start_time');

            $maxTime = PlanType::where('branch_id', getCurrentBranch())
                ->where('id', '!=', $data['id'])
                ->withTimeWindow()
                ->max('end_time');

            if ($minTime && $maxTime) {

                $globalStart = Carbon::parse($minTime);
                $globalEnd   = Carbon::parse($maxTime);

                if ($globalEnd->lessThanOrEqualTo($globalStart)) {
                    $globalEnd->addDay();
                }

                $newStart = Carbon::parse($request->start_time);
                $newEnd   = Carbon::parse($request->end_time);

                if ($newEnd->lessThanOrEqualTo($newStart)) {
                    $newEnd->addDay();
                }

                $finalStart = $newStart->lessThan($globalStart) ? $newStart : $globalStart;
                $finalEnd   = $newEnd->greaterThan($globalEnd) ? $newEnd : $globalEnd;

                $totalHours = $finalStart->diffInHours($finalEnd);

                if ($totalHours > $branchRecord->hour) {
                    return response()->json([
                        'error' => true,
                        'message' => 'You can’t add shift timings outside the library’s hours. Please adjust the timing.'
                    ]);
                }
            }

            $data = $request->except(['timming', 'duration_hours']);

            if ($request->image == 'orange') {
                $data['image'] = 'public/img/booked.png';
            } elseif ($request->image == 'light_orange') {
                $data['image'] = 'public/img/booked.png';
            } else {
                $data['image'] = 'public/img/booked.png';
            }
    
           if($request->day_type_id==1){
            $plan_type_name='Full Day';
           }elseif($request->day_type_id==2){
            $plan_type_name='First Half';
           }elseif($request->day_type_id==3){
            $plan_type_name='Second Half';
           }elseif($request->day_type_id==4){
            $plan_type_name='Hourly Slot 1';
           }elseif($request->day_type_id==5){
            $plan_type_name='Hourly Slot 2';
           }elseif($request->day_type_id==6){
            $plan_type_name='Hourly Slot 3';
           }elseif($request->day_type_id==7){
            $plan_type_name='Hourly Slot 4';
           }elseif($request->day_type_id==8){
            $plan_type_name='All Day';
           }elseif($request->day_type_id==9){
            $plan_type_name='Full Night';
           }elseif($request->day_type_id==10){
            $plan_type_name='Reserved';
           }elseif($request->day_type_id==11){
            $plan_type_name='VIP';
           }elseif($request->day_type_id==0){
            $plan_type_name=$request->custom_plan_type;
           }

            $data['name'] = $plan_type_name;
          
        }

        if ($request->seats) {
            $existingSeats = Hour::withoutGlobalScope('branch')->where('branch_id', $request->branch_id)->value('seats');

            if ($existingSeats !== null && $existingSeats > $request->seats) {
                return response()->json([
                    'error' => true,
                    'message' => 'Once you have created the seats, you can only increase the seat count; decreasing the number of seats is not allowed. Reducing the seat count after some seats have been booked may affect the system\'s functionality.'
                ]);
            }
        }

        // Only the operating-hours form sends `hour` (the seats form shares this model), and the
        // check must use the branch being edited, not the branch selected in the header.
        if ($request->databasemodel == 'Hour' && $request->filled('hour')
            && $request->hour < getBranchShiftTiming($request->branch_id ?: false)) {
            return response()->json([
                'error' => true,
                'message' => 'Branch Operating hour can not be less then shift hour.'
            ]);
        }

        if ($request->databasemodel == 'Floor') {

            $existing_total = Floor::where('branch_id', $request->branch_id)->sum('total_seats');
           
            $grand_branch_total = $branchRecord ? $branchRecord->seats : 0;

            $fromSeat = (int) $request->from_seat;
            $toSeat   = (int) $request->to_seat;
            $requested_total_seats = $toSeat - $fromSeat + 1;

            // If updating, subtract old total_seats for this floor
            if ($request->id && $request->id != 0) { 
                $oldSeats = Floor::where('id', $request->id)->value('total_seats') ?? 0;
                $existing_total -= $oldSeats;
            }

            if (($existing_total + $requested_total_seats) > $grand_branch_total) {
                return response()->json([
                    'error' => true,
                    'message' => 'Adding or updating this floor exceeds the total allowed seats for this branch. Maximum allowed: ' . $grand_branch_total
                ]);
            }

            unset($data['total_seats']);
            

        }
        if ($request->databasemodel == 'PlanType') {

            $exist_hour = Hour::where('branch_id', $request->branch_id)->value('hour');
           

            // VIP or Reserved must match full day hours
            if (in_array($request->day_type_id, [10, 11]) && $request->slot_hours != $exist_hour) {
                return response()->json([
                    'error' => true,
                    'message' => 'VIP and Reserved shift timing must be equal to Library hours.'
                ]);
            }
        }


    
        try {
            if($request->day_type_id!=0 || !isset($request->day_type_id) ){
             
                $this->conditionFunction($request,$plan_type_name);
            }
            
           
               
            unset($data['databasemodel']); 
            unset($data['databasetable']); 
            unset($data['_token']);
            unset($data['custom_plan_type']);
            
            if($request->redirect){
                $redirectUrl=$request->redirect;
            }else{
                $redirectUrl=null;
            }
            
            if($request->databasemodel=='Hour' && $request->branch_id){
                   $hour= DB::table('hour')->where('branch_id',$request->branch_id)->first();
                  $data['id']=$hour->id;
            }
             
            unset($data['redirect']);
             
            
          
            if($request->databasemodel ){
                if (is_null($data['id'])) {
                       
                    $modelInstance = $modelClass::create($data);
                } else {

                    // Hour rows are scoped to the header's branch; the seats / hours pages edit the
                    // branch picked in the branch list, so look it up without that scope.
                    $modelInstance = $request->databasemodel === 'Hour'
                        ? $modelClass::withoutGlobalScope('branch')->findOrFail($data['id'])
                        : $modelClass::findOrFail($data['id']);
                    if (!$this->ownsMasterRecord($modelInstance)) {
                        return response()->json(['error' => true, 'message' => 'Record not found.'], 404);
                    }

                    $modelInstance->update($data);
                }
            }

            if ($request->databasemodel === 'PlanPrice' && isset($modelInstance)) {
                app(\App\Services\ShiftTimingService::class)->syncDurationChildPrices($modelInstance);
            }

            return response()->json([
                'success' => true, 
                'message' => 'Data Added/Updated successfully',
                'plan' => $modelInstance , 
                'redirect'=>$redirectUrl
            ]);
        }  catch (Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
        
    }

    public function extendDay(Request $request){
       
        $request->validate([
            'extend_days' => 'required',
        ]);

        $id = $request->id;
        $user=Auth::user();
        if ($user->can('has-permission', 'Extend Seat')){
            $extend_day = $request->extend_days;
        } else {
            $extend_day = 0;
        }
        
       
        if(DB::table('hour')->where('library_id', $request->library_id)){

            $hourData=DB::table('hour')->where('library_id', $request->library_id)->update([
                'extend_days'=>$extend_day
            ]);
        }else{
            return response()->json([
                'error' => true,
                'message' => 'Please Add Hour'
            ], 400);
        }
        
       
        return response()->json([
            'success' => true, 
            'message' => 'Extend Days Added/Updated successfully',
            'hour' => $hourData  
        ]);


    }
    
    public function masterEdit(Request $request){
        
        $id=$request->id;
      
        try {
            $modelClass = $this->resolveMasterModel($request->modeltable);
            if (!$modelClass) {
                return response()->json(['success' => false, 'message' => 'Invalid master type.'], 422);
            }
            $data=$modelClass::findOrFail($id);
            if (!$this->ownsMasterRecord($data)) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }

            return response()->json([$request->modeltable => $data]);
           
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
    public function planTypeView()
    {
        return $this->customManagementPage('shifts');
    }
    public function planTypeCreate($id = null)
    {
        // Forms now open as a modal on the Custom Management page.
        return redirect()->route('plantype.index', $id ? ['edit' => $id] : ['add' => 1]);
    }
    public function floorView()
    {
        return $this->customManagementPage('floors');
    }
    public function floorCreate($id = null)
    {
        // Forms now open as a modal on the Custom Management page.
        return redirect()->route('floor.index', $id ? ['edit' => $id] : ['add' => 1]);
    }
    

    public function planView()
    {
        return $this->customManagementPage('plans');
    }
    public function planCreate($id = null)
    {
        // Forms now open as a modal on the Custom Management page.
        return redirect()->route('plan.index', $id ? ['edit' => $id] : ['add' => 1]);
    }

    public function expenseView()
    {
        return $this->customManagementPage('expenses');
    }
    public function expenseCreate($id = null)
    {
        // Forms now open as a modal on the Custom Management page.
        return redirect()->route('expense.index', $id ? ['edit' => $id] : ['add' => 1]);
    }

    /*
     * Seats, operating hours, grace days, locker fee and token money of one branch: one page each,
     * all drawn by master/branch-setting. Only this library's branches can be opened.
     */
    private function branchSettingPage($id, string $key)
    {
        $branch = Branch::where('library_id', getLibraryId())->find($id ?: getCurrentBranch());
        if (!$branch) {
            return redirect()->route('branch.list')->with('error', 'Branch not found.');
        }
        $hourRow = DB::table('hour')->where('branch_id', $branch->id)->first();
        if (in_array($key, ['seats', 'hour'], true) && !$hourRow) {
            return redirect()->route('branch.list')->with('error', 'Set up this branch first.');
        }

        $longestShift = (float) getBranchShiftTiming($branch->id);
        $settings = [
            'seats' => [
                'title' => 'Total seats', 'icon' => 'armchair', 'form' => 'Hour', 'type' => 'number', 'suffix' => 'seats',
                'value' => $hourRow->seats ?? '', 'min' => max(1, (int) ($hourRow->seats ?? 1)), 'max' => 9999,
                'info' => 'The number of seats learners can book in this branch. It shows on the seat map and the booking form.',
                'hint' => 'You can only add seats. Removing seats is not allowed once they exist.',
            ],
            'hour' => [
                'title' => 'Operating hours', 'icon' => 'clock-3', 'form' => 'Hour', 'type' => 'timing',
                'value' => $hourRow->hour ?? '', 'min' => 0, 'max' => 24,
                'times' => \App\Services\BranchHoursService::times($branch->id),
                'all_day' => (int) ($hourRow->hour ?? 0) >= 24,
                'info' => 'When the library opens and closes. Every shift has to fit inside these hours, and advanced shifts (fixed hours, flexible hours, direct booking) are booked only inside them.',
                'hint' => $longestShift > 0 ? 'Your shifts run across ' . rtrim(rtrim(number_format($longestShift, 2), '0'), '.') . ' hrs of the day, so the library has to stay open at least that long and around their times.' : '',
            ],
            'extend_days' => [
                'title' => 'Grace days (Extend period)', 'icon' => 'hourglass', 'form' => 'Branch', 'type' => 'number', 'suffix' => 'days',
                'value' => $branch->extend_days ?? 0, 'min' => 0, 'max' => 30,
                'info' => 'Days a learner keeps the seat after the plan ends, so they have time to renew.',
                'hint' => '0 to 30 days. 0 frees the seat on the day the plan ends.',
            ],
            'locker_amount' => [
                'title' => 'Locker fee', 'icon' => 'lock', 'form' => 'Branch', 'type' => 'number', 'prefix' => '₹', 'suffix' => 'a month',
                'value' => $branch->locker_amount ?? 0, 'min' => 0, 'max' => 99999,
                'info' => 'Added to the fee when a learner takes a locker. Longer plans charge it for every month.',
                'hint' => 'Put 0 if you don\'t offer lockers.',
            ],
            'token_money' => [
                'title' => 'Token money', 'icon' => 'hand-coins', 'form' => 'Branch', 'type' => 'number', 'prefix' => '₹',
                'value' => $branch->token_money ?? 0, 'min' => 0, 'max' => 99999,
                'info' => 'Advance a learner pays to hold a seat when they book.',
                'hint' => 'Put 0 if you don\'t take an advance.',
            ],
        ];

        return view('master.branch-setting', [
            'branch' => $branch,
            'key' => $key,
            'setting' => $settings[$key],
        ]);
    }

    public function seatCreate($id = null)
    {
        return $this->branchSettingPage($id, 'seats');
    }

    public function hourCreate($id = null)
    {
        return $this->branchSettingPage($id, 'hour');
    }

    public function extendDayCreate($id = null)
    {
        return $this->branchSettingPage($id, 'extend_days');
    }

    public function lockerAmountCreate($id = null)
    {
        return $this->branchSettingPage($id, 'locker_amount');
    }

    public function tokenAmountCreate($id = null)
    {
        return $this->branchSettingPage($id, 'token_money');
    }
    
    public function examView()
    {
        return $this->customManagementPage('exams');
    }
    public function examCreate($id = null)
    {
        // Forms now open as a modal on the Custom Management page.
        return redirect()->route('exam.index', $id ? ['edit' => $id] : ['add' => 1]);
    }
    

    public function activeDeactive(Request $request, $id)
    {
      
        $modelClass = $this->resolveMasterModel($request->dataTable);

        if (!$modelClass) {
            return response()->json(['status' => 'error', 'message' => 'Invalid model'], 400);
        }

        if ($request->dataTable == 'Hour') {
            $hour = Hour::find($id);
            if ($hour && $this->ownsMasterRecord($hour)) {
                $hour->update(['extend_days' => null]);
                return response()->json(['status' => 'success', 'message' => 'Hour successfully updated', 'data_status' => 'updated']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Hour not found'], 404);
            }
        } else {
            $data = $modelClass::withTrashed()->find($id);

            if ($data && $this->ownsMasterRecord($data)) {
                if (in_array($request->dataTable, ['Plan', 'PlanType'], true) && $this->hasActiveLearnersForMaster($request->dataTable, (int) $id)) {
                    return response()->json(['status' => 'error', 'message' => 'Active learners are assigned to this record.'], 422);
                }
                if ($data->trashed()) {
                    $data->restore();
                    $status = 'activated';
                } else {
                    $data->delete();
                    $status = 'deactivated';
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Data successfully ' . $status,
                    'data_status' => $status
                ]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Data not found'], 404);
            }
        }
    }
    public function deleteMaster(Request $request, $id){
       
        $table = $request->input('table');
        $deleteType = $request->input('deleteType'); // soft | permanent

        $modelClass = $this->resolveMasterModel($table);

        if (!$modelClass) {
            return response()->json(['status' => 'error', 'message' => 'Invalid model'], 400);
        }
        $data = $modelClass::withTrashed()->find($id);
        if (!$data || !$this->ownsMasterRecord($data)) {
            return response()->json(['status' => 'error', 'message' => 'Data not found'], 404);
        }

        if (in_array($table, ['Plan', 'PlanType'], true) && $this->hasActiveLearnersForMaster($table, (int) $id)) {
            return response()->json([
                'status' => 'error',
                'message' => $table === 'Plan'
                    ? 'This plan cannot be edited, deactivated, or deleted because active learners are assigned to it.'
                    : 'This plan type cannot be edited, deactivated, or deleted because active learners are assigned to it.'
            ], 422);
        }

        // the open branch and a library's only branch stay (the panel works on them)
        if ($table === 'Branch'
            && ((int) getCurrentBranch() === (int) $id || Branch::where('library_id', $data->library_id)->count() <= 1)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can\'t delete the branch that is open now or your only branch. Switch to another branch first.'
            ], 422);
        }

        if ($deleteType === 'permanent') {

            if ($this->masterRecordInUse($table, (int) $id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This record is used by learners or bookings, so it cannot be deleted permanently. You can deactivate it instead.'
                ], 422);
            }

            DB::transaction(function () use ($table, $id, $data) {
                // Prices of a removed shift would otherwise stay behind as orphans.
                if ($table === 'PlanType') {
                    PlanPrice::withoutGlobalScopes()->where('plan_type_id', $id)->forceDelete();
                }
                $data->forceDelete();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data permanently deleted',
                'data_status' => 'permanently deleted'
            ]);
        }

        // For models with soft deletes
        if (method_exists($modelClass, 'trashed') && $data->trashed()) {
            if ($table === 'Floor') {
                $capacityError = $this->floorCapacityError((int) $data->branch_id, (int) $data->total_seats, (int) $data->id);
                if ($capacityError) {
                    return response()->json(['status' => 'error', 'message' => $capacityError], 422);
                }
            }
            $data->restore();
            $status = 'activated';
        } else {
            $data->delete();
            $status = 'deactivated';
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data successfully ' . $status,
            'data_status' => $status
        ]);
    }

    protected function conditionFunction(Request $request, $day_type = null)
    {
        
        $modelClass = 'App\\Models\\' . $request->databasemodel;
        $check_from_id = null;
        $check_to_id = null;

        if ($request->databasemodel == 'Plan') {
            $check_from_id = 'plan_id';
            $check_to_id = $request->plan_id;
            $check_from_type='type';
            $check_to_type=$request->type;
        } elseif ($request->databasemodel == 'PlanType') {
            $check_from_id = 'day_type_id';
            $check_to_id = $request->day_type_id;
        } elseif ($request->databasemodel != 'PlanPrice') {
            // Only Plan / PlanType / PlanPrice have a duplicate rule; everything else is saved as-is.
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong',
            ]);
        }

        
        if ($request->databasemodel == 'Plan'  ) {
           
            $query = $modelClass::where($check_from_id, $check_to_id)->where($check_from_type, $check_to_type)
                ->where('library_id', $request->library_id);

        }
        elseif ($request->databasemodel == 'PlanType'  ) {
           
            $query = $modelClass::where($check_from_id, $check_to_id)
                ->where('branch_id', $request->branch_id);

        }elseif($request->databasemodel == 'PlanPrice'){
            $query = $modelClass::where('plan_id', $request->plan_id)->where('plan_type_id',$request->plan_type_id)
            ->where('library_id', $request->library_id)->where('branch_id', $request->branch_id);
            
        }elseif($request->databasemodel=='Hour'){
            
            $query =Hour::where('branch_id', $request->branch_id);
            if($request->hour <24 && (PlanType::where('day_type_id',1)->value('slot_hours') != $request->hour)){
                throw new \Exception('Hour Not valid');
            }
        }else{
            $query = $modelClass::where($check_from_id, $check_to_id)
            ->where('library_id', $request->library_id);

        }

        if (!empty($request->id)) {
            $query->where('id', '!=', $request->id);
        }
        $existing = $query->count();

        if ($existing > 0) {
            throw new \Exception('Data already exists.');
        }
    }


    protected function validationfunction(Request $request){
     
        if ($request->databasemodel == 'Plan'){
            $request->validate([
                'type' => 'required|in:DAY,WEEK,MONTH,YEAR',
                'plan_id' => 'required|integer|min:1|max:999',
                'monthdays' => 'nullable|integer|min:1',
            ]);
        }
       
        if($request->databasemodel == 'PlanType'){
           
            $request->validate([
                'day_type_id' => 'required|integer',
                'custom_plan_type' => 'required_if:day_type_id,0|nullable|string|max:100',
                'start_time' => ['required', 'regex:/^([01]\d|2[0-4]):[0-5]\d(:[0-5]\d)?$/'],
                'end_time' => ['required', 'regex:/^([01]\d|2[0-4]):[0-5]\d(:[0-5]\d)?$/'],
                'slot_hours' => 'required', 
                'branch_id' => ['required','not_in:0'],
            ], [
                'custom_plan_type.required_if' => 'Please enter the custom shift name.',
                'start_time.regex' => 'Please select a valid start time.',
                'end_time.regex' => 'Please select a valid end time.',
            ]);
             $request->merge([
                'start_time' => $request->start_time === '00:00' ? '24:00' : $request->start_time,
                'end_time'   => $request->end_time   === '00:00' ? '24:00' : $request->end_time,
            ]);
            // The slot length is derived from the times instead of trusting the read-only field.
            $request->merge(['slot_hours' => $this->slotHoursBetween($request->start_time, $request->end_time)]);
        }
        if($request->databasemodel == 'PlanPrice'){
            $request->validate([
                'plan_id' => ['required', Rule::exists('plans', 'id')->where('library_id', getLibraryId())],
                'plan_type_id' => ['required', Rule::exists('plan_types', 'id')->where('branch_id', $request->branch_id)],
                'price' => 'required|numeric|min:0',
                'branch_id' => ['required','not_in:0'],
            ]);
        }
        // Branch settings pages: each posts one of these fields (sometimes = only when it is sent)
        $settingNames = ['extend_days' => 'grace days', 'locker_amount' => 'locker fee', 'token_money' => 'token money', 'hour' => 'operating hours', 'seats' => 'total seats'];
        if($request->databasemodel == 'Branch'){
            $request->validate([
                'extend_days' => 'sometimes|required|integer|min:0|max:30',
                'locker_amount' => 'sometimes|required|integer|min:0|max:99999',
                'token_money' => 'sometimes|required|integer|min:0|max:99999',
            ], [], $settingNames);
        }
        if($request->databasemodel == 'Hour'){
           $validator = Validator::make($request->all(), [
                'hour' => 'sometimes|required|integer|min:10|max:' . (Gate::allows('has-permission', 'All Day') ? 24 : 23),
                'seats' => 'sometimes|required|integer|min:1|max:9999',
            ], [], $settingNames);
            return $validator->validated();
        }
        
        if ($request->databasemodel == 'Expense'){
            $request->validate([
                // ignore() lets an expense be saved again without changing its name.
                'name' => ['required', 'string', 'max:191', Rule::unique('expenses', 'name')->ignore($request->id)],
            ]);
        }
        if ($request->databasemodel == 'Exam'){
            $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('exams', 'name')->ignore($request->id)],
            ]);
        }
        if ($request->databasemodel == 'BooksCategory'){
            $request->validate([
                'category_name' => 'required',
                'sub_category_name' => 'required',
            ]);
        }
        if ($request->databasemodel == 'Floor') {
            // The floor form posts single values, so the old `field.*` rules never ran.
            $request->validate([
                'floor_no'   => 'required|integer|min:0|max:500',
                'name'       => 'nullable|string|max:255',
                'from_seat'  => 'required|integer|min:1',
                'to_seat'    => 'required|integer|min:1|gte:from_seat',
                'branch_id'  => ['required', 'not_in:0'],
            ], [
                'floor_no.required' => 'Floor number is required.',
                'from_seat.required' => 'From Seat is required.',
                'to_seat.required' => 'To Seat is required.',
                'to_seat.gte' => 'To Seat must be greater than or equal to From Seat.',
            ]);
        }
    }

    public function featureCreate(Request $request, $id = null){
        $feature = null;
        $features=Feature::get();
        return view('master.features',compact('features','feature'));
    }
    public function featureEdit($id)
    {
        $feature = Feature::findOrFail($id);
        $features = Feature::all();
        return view('master.features', compact('feature', 'features'));
    }

    public function destroy($id)
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();

        return redirect()->route('feature.create')->with('success', 'Feature deleted successfully!');
    }
   
    public function storeFeature(Request $request, $id = null)
    {
       
        $rules = [
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    
        $request->validate($rules);
    
        $data = $request->only('name');

        if ($request->hasFile('image') ) {
           
           $image = $request->file('image');
            $imageName = 'icon_' . time() . '.' . $image->getClientOriginalExtension();

            $destinationPath = public_path('upload/icon');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }

            $image->move($destinationPath, $imageName);

            $data['image'] = 'upload/icon/' . $imageName;
            if ($id) {
                $feature = Feature::findOrFail($id);
                if ($feature->image && file_exists(public_path($feature->image))) {
                    unlink(public_path($feature->image));
                }
            }
        } elseif ($id) {
            $feature = Feature::findOrFail($id);
            $data['image'] = $feature->image;
        }

        if ($id) {
            $feature = Feature::findOrFail($id);
            $feature->update($data);
            $message = 'Feature updated successfully!';
        } else {
          
            Feature::create($data);
            $message = 'Feature added successfully!';
        }

        return redirect()->route('feature.create')->with('success', $message);
    }

  

    public function getLibraries(Request $request)
    {
        $query = $request->input('query');
        $suggestion = $request->input('suggestion');
        $city = $request->input('city');
        $category = $request->input('category', 'all');

        $buildBaseQuery = function () use ($suggestion, $query, $city) {
            $q = DB::table('branches as b')
                ->join('libraries as l', 'l.id', '=', 'b.library_id')
                ->leftJoin('cities as c', 'c.id', '=', 'b.city_id')
                ->leftJoin('hour as h', 'h.branch_id', '=', 'b.id')
                ->leftJoin('feedback as f', 'f.library_id', '=', 'l.id')
                ->where('b.is_profile', 1)
                ->whereNull('b.deleted_at')
                ->whereNull('l.deleted_at')
                ->select(
                    'b.id as id',
                    'b.library_id',
                    'b.library_address',
                    'b.name as library_name',
                    'b.google_map',
                    'b.state_id',
                    'b.city_id',
                    'c.city_name',
                    'b.library_logo',
                    'b.slug',
                    'h.seats',
                    'b.display_name',
                    'b.library_category',
                    'l.is_paid',
                    'b.features',
                    'b.mobile',
                    'b.library_images',
                    'b.description',
                    DB::raw('ROUND(COALESCE(AVG(f.rating), 4.8), 1) as rating'),
                    DB::raw('COUNT(f.id) as review_count')
                )
                ->groupBy(
                    'b.id',
                    'b.library_id',
                    'b.library_address',
                    'b.name',
                    'b.google_map',
                    'b.state_id',
                    'b.city_id',
                    'c.city_name',
                    'b.library_logo',
                    'b.slug',
                    'h.seats',
                    'b.display_name',
                    'b.library_category',
                    'l.is_paid',
                    'b.features',
                    'b.mobile',
                    'b.library_images',
                    'b.description'
                );

            if ($suggestion) {
                $q->where(function ($sub) use ($suggestion) {
                    $sub->where('b.name', 'like', "%$suggestion%")
                      ->orWhere('b.display_name', 'like', "%$suggestion%")
                      ->orWhere('b.library_address', 'like', "%$suggestion%");
                });
            } elseif ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('b.name', 'like', "%$query%")
                      ->orWhere('b.display_name', 'like', "%$query%")
                      ->orWhere('b.library_address', 'like', "%$query%");
                });
            }

            if ($city) {
                $q->where('b.city_id', $city);
            }

            return $q;
        };

        // If grouped is requested (for separate categorized lists on directory page)
        if ($category === 'grouped') {
            $premium = $buildBaseQuery()->where('l.is_paid', 1)->orderBy('b.id', 'desc')->take(12)->get();
            $topRated = $buildBaseQuery()->where(function ($q) {
                $q->where('f.rating', '>=', 4)
                  ->orWhere(function ($sub) {
                      $sub->where('l.is_paid', 1)->where('h.seats', '>=', 40);
                  });
            })->orderBy('rating', 'desc')->orderBy('b.id', 'desc')->take(10)->get();
            $other = $buildBaseQuery()->where('l.is_paid', 0)->orderBy('b.id', 'desc')->take(10)->get();

            $totalPremium = $buildBaseQuery()->where('l.is_paid', 1)->get()->count();
            $totalTopRated = $buildBaseQuery()->where(function ($q) {
                $q->where('f.rating', '>=', 4)
                  ->orWhere(function ($sub) {
                      $sub->where('l.is_paid', 1)->where('h.seats', '>=', 40);
                  });
            })->get()->count();
            $totalOther = $buildBaseQuery()->where('l.is_paid', 0)->get()->count();

            return response()->json([
                'status' => 'grouped',
                'premium' => $premium,
                'top_rated' => $topRated,
                'other' => $other,
                'counts' => [
                    'premium' => $totalPremium,
                    'top_rated' => $totalTopRated,
                    'other' => $totalOther,
                    'total' => $totalPremium + $totalOther
                ]
            ]);
        }

        $libraries = $buildBaseQuery();

        // Apply category filter (premium, top_rated, other)
        if ($category === 'premium') {
            $libraries->where('l.is_paid', 1);
        } elseif ($category === 'top_rated') {
            $libraries->where(function ($q) {
                $q->where('f.rating', '>=', 4)
                  ->orWhere(function ($sub) {
                      $sub->where('l.is_paid', 1)->where('h.seats', '>=', 40);
                  });
            });
        } elseif ($category === 'other') {
            $libraries->where('l.is_paid', 0);
        }

        // Prioritize paid/verified libraries first, then latest
        $libraries->orderBy('l.is_paid', 'desc')
                  ->orderBy('b.id', 'desc');

        $results = $libraries->take(100)->get();

        return response()->json($results);
    }

    public function planPriceView()
    {
        return $this->customManagementPage('prices');
    }
    public function planPriceCreate($id = null)
    {
        // Forms now open as a modal on the Custom Management page.
        return redirect()->route('planPrice.index', $id ? ['edit' => $id] : ['add' => 1]);
    }

    public function menu(){
        return view('master.menu');
    }

    public function toggleFeature()
    {
        Gate::authorize('has-permission', self::PERMISSION_MANAGE_FEATURE_TOGGLES);

        return $this->customManagementPage('toggles');
    }

    public function updateHidefield(Request $request)
    {
        Gate::authorize('has-permission', self::PERMISSION_MANAGE_FEATURE_TOGGLES);

        $branch = Branch::where('id', getCurrentBranch())->first();

        if ($branch) {
            // only known options are kept: registry keys + toggle_features ids (App\Support\FeatureToggles)
            $hidden = \App\Support\FeatureToggles::normalise((array) $request->input('hidden_ids', []));
            $branch->hide_field = $hidden ? json_encode($hidden) : null;
            $branch->save();
            \App\Support\FeatureToggles::flush();

            return response()->json(['status' => true, 'message' => 'Saved', 'hidden' => $hidden]);
        }

        return response()->json(['status' => false, 'message' => 'Branch not found'], 404);
    }

    public function deletePlanType(Request $request)
    {
        
        $planType = PlanType::where('id', $request->id)
            ->where('branch_id', getCurrentBranch())
            ->first();

        if (!$planType) {
            return response()->json([
                'status' => false,
                'message' => 'Shift not found'
            ]);
        }

        $exists = $this->hasActiveLearnersForMaster('PlanType', (int) $request->id);
               
               
        if (!empty($request->id) && $exists) {
            return response()->json([
                'error' => true,
                'message' => 'This plan type cannot be deleted because one or more learners are currently enrolled in it. Please remove or reassign the learners before deleting this plan type.'
            ], 422);
        }


       DB::beginTransaction();

            try {
                 PlanPrice::where('plan_type_id', $planType->id)->forceDelete();
                 $planType->forceDelete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Shift deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete shift'
            ], 500);
        }

    }

    /**
     * Shift booking mode (docs/ADVANCED_SHIFTS.md). Only custom shifts (day_type 0) can be
     * duration/free/flexible; every other shift stays fixed. start/end is the window the
     * timing must fall in, and slot_hours is the booked length (duration, flexible) or the
     * window (free).
     */
    private function normalizeShiftBookingMode(Request $request): ?string
    {
        $mode = (string) $request->input('booking_mode', PlanType::MODE_FIXED);
        $templateModes = array_merge(PlanType::TIMED_TEMPLATE_MODES, [PlanType::MODE_FLEXIBLE]);
        if ((string) $request->day_type_id !== '0' || !in_array($mode, $templateModes, true)) {
            $request->merge(['booking_mode' => PlanType::MODE_FIXED]);
            return null;
        }

        $toMinutes = function ($time) {
            return preg_match('/^(\d{1,2}):(\d{2})/', (string) $time, $m) ? ((int) $m[1] * 60 + (int) $m[2]) % 1440 : null;
        };
        $start = $toMinutes($request->start_time);
        $end = $toMinutes($request->end_time);
        if ($start === null || $end === null) {
            return 'Enter the time range this shift can be booked in.';
        }
        $windowMinutes = $end > $start ? $end - $start : $end + 1440 - $start;

        if (in_array($mode, [PlanType::MODE_DURATION, PlanType::MODE_FLEXIBLE], true)) {
            $hours = (float) $request->input('duration_hours');
            if ($hours <= 0 || $hours * 60 > $windowMinutes) {
                return 'Shift hours must be more than 0 and fit inside the selected time range.';
            }
            $request->merge(['slot_hours' => rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.')]);
        } else {
            $request->merge(['slot_hours' => rtrim(rtrim(number_format($windowMinutes / 60, 2, '.', ''), '0'), '.')]);
        }

        $request->merge(['booking_mode' => $mode]);

        return null;
    }

    private function hasActiveLearnersForMaster(string $table, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $query = LearnerDetail::where('status', 1);

        if ($table === 'Plan') {
            return $query->where('plan_id', $id)
                ->where('library_id', getLibraryId())
                ->exists();
        }

        if ($table === 'PlanType') {
            // A timed template is in use when any of its hidden timing rows is
            $ids = PlanType::withoutGlobalScopes()
                ->where(fn ($q) => $q->where('id', $id)->orWhere('parent_id', $id))
                ->pluck('id');

            return $query->whereIn('plan_type_id', $ids)
                ->where('branch_id', getCurrentBranch())
                ->exists();
        }

        return false;
    }

    /**
     * Resolves a model name posted to the generic master endpoints. Library owners/staff
     * are limited to LIBRARY_MASTER_MODELS; the superadmin route keeps its old behaviour.
     */
    private function resolveMasterModel(?string $name): ?string
    {
        if (!$name || !preg_match('/^[A-Za-z]+$/', $name)) {
            return null;
        }

        $modelClass = 'App\\Models\\' . $name;
        if (!class_exists($modelClass)) {
            return null;
        }

        if (!$this->isLibraryRequest()) {
            return $modelClass;
        }

        return in_array($name, self::LIBRARY_MASTER_MODELS, true) ? $modelClass : null;
    }

    private function isLibraryRequest(): bool
    {
        return Auth::guard('library')->check() || Auth::guard('library_user')->check();
    }

    private function ownsBranch($branchId): bool
    {
        if (!$this->isLibraryRequest()) {
            return true;
        }

        return Branch::where('id', $branchId)->where('library_id', getLibraryId())->exists();
    }

    /**
     * A record belongs to the current library when its library_id / branch_id point to it.
     * Expense and Exam rows have neither column (they are shared lists), so they pass.
     */
    private function ownsMasterRecord($record): bool
    {
        if (!$this->isLibraryRequest()) {
            return true;
        }

        if ($record instanceof Branch) {
            return (int) $record->library_id === (int) getLibraryId();
        }

        $attributes = $record->getAttributes();

        if (array_key_exists('library_id', $attributes) && $record->library_id !== null
            && (int) $record->library_id !== (int) getLibraryId()) {
            return false;
        }

        if (array_key_exists('branch_id', $attributes) && !empty($record->branch_id)
            && !$this->ownsBranch($record->branch_id)) {
            return false;
        }

        return true;
    }

    /** Whether learners or bookings still point at the record (blocks permanent delete). */
    private function masterRecordInUse(string $table, int $id): bool
    {
        switch ($table) {
            case 'Plan':
                return LearnerDetail::withoutGlobalScopes()->where('plan_id', $id)->exists();
            case 'PlanType':
                return LearnerDetail::withoutGlobalScopes()->where('plan_type_id', $id)->exists()
                    || DB::table('plan_prices')->join('bookings', 'bookings.plan_price_id', '=', 'plan_prices.id')
                        ->where('plan_prices.plan_type_id', $id)->exists();
            case 'PlanPrice':
                return LearnerDetail::withoutGlobalScopes()->where('plan_price_id', $id)->exists()
                    || DB::table('bookings')->where('plan_price_id', $id)->exists();
            case 'Expense':
                return DB::table('monthly_expense')->where('expense_id', $id)->exists();
            case 'Exam':
                return LearnerDetail::withoutGlobalScopes()->where('exam_id', $id)->exists();
            case 'Branch':
                // The branch list "Delete" button sends a permanent delete; never drop a branch with learners.
                return Learner::withoutGlobalScopes()->where('branch_id', $id)->exists();
        }

        return false;
    }

    /**
     * Seat numbers inside a floor are floor-local (every floor may start at 1, see
     * generateSeatNumbers2()), so only the total seat count is limited by the branch.
     * Returns an error message when the floor no longer fits, otherwise null.
     */
    private function floorCapacityError(int $branchId, int $floorSeats, ?int $ignoreId = null): ?string
    {
        $branchSeats = (int) Hour::withoutGlobalScopes()->where('branch_id', $branchId)->whereNull('deleted_at')->value('seats');
        $usedSeats = (int) Floor::where('branch_id', $branchId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->sum('total_seats');

        if ($usedSeats + $floorSeats > $branchSeats) {
            return "Not enough seats left in this branch. Maximum allowed: {$branchSeats}, already allocated: {$usedSeats}.";
        }

        return null;
    }

    /** Whole hours between two H:i times; equal times mean a 24 hour slot (same as the form). */
    private function slotHoursBetween(?string $start, ?string $end): int
    {
        $toMinutes = function (?string $time): int {
            [$hours, $minutes] = array_pad(explode(':', (string) $time), 2, 0);
            return ((int) $hours * 60) + (int) $minutes;
        };

        $diff = $toMinutes($end) - $toMinutes($start);
        if ($diff === 0) {
            return 24;
        }
        if ($diff < 0) {
            $diff += 24 * 60;
        }

        return intdiv($diff, 60);
    }

    /** Single Custom Management page: every master list in tabs, every form in a modal. */
    private function customManagementPage(string $activeTab)
    {
        $branchId = (int) getCurrentBranch();
        $branchHour = $branchId ? Hour::withoutGlobalScopes()->where('branch_id', $branchId)->whereNull('deleted_at')->first() : null;

        $cmFloors = $branchId
            ? Floor::withTrashed()->where('branch_id', $branchId)->orderBy('floor_no')->orderBy('from_seat')->get()
            : collect();

        $cmPlans = Plan::withTrashed()
            ->listable()
            ->select('plans.*')
            ->selectSub(function ($query) {
                $query->from('learner_detail')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('learner_detail.plan_id', 'plans.id')
                    ->where('learner_detail.status', 1)
                    ->where('learner_detail.library_id', getLibraryId());
            }, 'active_learners_count')
            ->orderByRaw("FIELD(type, 'DAY', 'WEEK', 'MONTH', 'YEAR')")
            ->orderBy('plan_id')
            ->get();

        $cmPlanTypes = $branchId
            ? PlanType::withTrashed()
                ->select('plan_types.*')
                ->selectSub(function ($query) use ($branchId) {
                    // A timed template counts the learners booked on its hidden timing rows too
                    $query->from('learner_detail')
                        ->selectRaw('COUNT(*)')
                        ->whereRaw('learner_detail.plan_type_id IN (SELECT pt_c.id FROM plan_types pt_c WHERE pt_c.id = plan_types.id OR pt_c.parent_id = plan_types.id)')
                        ->where('learner_detail.status', 1)
                        ->where('learner_detail.branch_id', $branchId);
                }, 'active_learners_count')
                ->listable()
                ->orderBy('start_time')
                ->get()
            : collect();

        $cmPlanPrices = $branchId
            ? PlanPrice::withTrashed()->where('branch_id', $branchId)->with([
                'plan' => fn ($query) => $query->withTrashed(),
                'planType' => fn ($query) => $query->withTrashed(),
            ])->get()
                // Prices copied onto hidden timing rows are managed through their template
                ->reject(fn ($price) => $price->planType && $price->planType->parent_id)
                ->values()
            : collect();

        $cmExpenses = Expense::withTrashed()->orderBy('name')->get();
        $cmExams = Exam::withTrashed()->orderBy('name')->get();

        $canManageToggles = Gate::allows('has-permission', self::PERMISSION_MANAGE_FEATURE_TOGGLES);
        $toggleFeatures = $canManageToggles ? DB::table('toggle_features')->orderBy('category')->orderBy('id')->get()->groupBy('category') : collect();
        $hiddenFields = [];
        if ($canManageToggles && $branchId) {
            $hideField = Branch::where('id', $branchId)->value('hide_field');
            $hiddenFields = $hideField ? (json_decode($hideField, true) ?? []) : [];
        }

        return view('master.custom-management', compact(
            'activeTab', 'branchId', 'branchHour', 'cmFloors', 'cmPlans', 'cmPlanTypes', 'cmPlanPrices',
            'cmExpenses', 'cmExams', 'canManageToggles', 'toggleFeatures', 'hiddenFields'
        ));
    }

  public function getPriceMaster(Request $request)
    {
        $plan_type_id = $request->plan_type_id;
        $plan_id      = $request->plan_id;
        $branchId     = getCurrentBranch();

        if (!$plan_type_id || !$plan_id) {
            return response()->json(0);
        }

        $PlanpPrice = getPlanPrice(
                $plan_id,
                $plan_type_id
            );
        return response()->json($PlanpPrice);
    }





}
