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
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Exception;
use DB;
use Yajra\DataTables\Facades\DataTables;
use Auth;
use Illuminate\Support\Facades\Log;

class MasterController extends Controller
{
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
        $subscriptions = Subscription::all();
        $permissions = Permission::where('guard_name','library')->get();
        $users = User::all();

        return view('master.subscriptionPermission', compact('subscriptions', 'permissions', 'users'));
    }

    public function showPlanwisePermission($id){
       
        // $subscriptions = Subscription::all();
        $subscriptions = Subscription::where('id',$id)->get();
        $permissions = Permission::where('guard_name','library')->get();
        $users = User::all();
        return view('master.showPlanwisePermissions', compact('subscriptions', 'permissions', 'users'));
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
        ]);
        Subscription::create($request->all());
        return redirect()->back()->with('success', 'Subscription created successfully');
    }
    public function subscriptionMasterUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'monthly_fees' => 'required|numeric|min:0',
            'yearly_fees' => 'nullable|numeric|min:0',
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
    public function managePermissions($permissionId = null,$categoryId = null)
    {
        
        $subscriptions = Subscription::with('permissions')->get();
        $permissions =  Permission::get(); 
        $permission = $permissionId ? Permission::find($permissionId) : null;
        $category = $categoryId ? PermissionCategory::find($categoryId) : null;
        $categories = PermissionCategory::all();
        return view('master.permissions', compact('subscriptions', 'permission','permissions','categories','category'));
    }
    public function storeOrUpdateCategory(Request $request, $categoryId = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            
        ]);

        PermissionCategory::updateOrCreate(['id' => $categoryId], $data);

        return redirect()->route('permissions')->with('success', 'Permission Category saved successfully.');
    }

    public function storeOrUpdatePermission(Request $request, $permissionId = null)
    {
     
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
           'permission_category_id' => 'required|exists:permission_categories,id',
        ]);

        
        $exists =  Permission::where('name',$request->name)->where('guard_name',$request->guard_name)->exists();
      
        if($exists){
            $message = $request->name.' Permission is already exists.';
            return redirect()->route('permissions') ->with('warning', $message);
        }

        if ($permissionId) {
          
            $permission = Permission::findOrFail($permissionId);
            $permission->update($request->only('name', 'description', 'guard_name', 'permission_category_id'));
            $message = 'Permission updated successfully.';
        } else {
            
            $permission = Permission::create($request->only('name', 'description', 'guard_name', 'permission_category_id'));
          
            $message = 'Permission added successfully.';
        }

        return redirect()->route('permissions') ->with('success', $message);
    }


  
    public function deletePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();

        return redirect()->route('permissions')
            ->with('success', 'Permission deleted successfully.');
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
         $plans=Plan::where('library_id',getLibraryId())->withTrashed()->get();
            
            $plantype=PlanType::withTrashed()->where('library_id',getLibraryId())->get();
            $plantypes=PlanType::where('library_id',getLibraryId())->get();
           
           
            $seat_button=Library::where('id',getLibraryId())->where('status',1)->exists();
            
            
            $notleaner=Learner::where('id',getAuthenticatedUser()->id)->count();
        if(getCurrentBranch()==0){
               $hours=Hour::where('library_id',getLibraryId())->withTrashed()->get();
               $is_extendday=Branch::where('library_id',getLibraryId())->whereNotNull('extend_days')->exists();
               $branches=Branch::where('library_id',getLibraryId())->get();
                $planprice=PlanPrice::where('library_id',getLibraryId())->withTrashed()->with(['plan', 'planType'])->get();
                $expenses=Expense::get();
        }else{
            $hours=Hour::where('library_id',getLibraryId())->where('branch_id',getCurrentBranch())->withTrashed()->get();
            $is_extendday=Branch::where('library_id',getLibraryId())->where('id',getCurrentBranch())->whereNotNull('extend_days')->exists();
            $branches=Branch::where('library_id',getLibraryId())->where('id',getCurrentBranch())->get();
             $planprice=PlanPrice::where('library_id',getLibraryId())->where('branch_id',getCurrentBranch())->withTrashed()->with(['plan', 'planType'])->get();
             $expenses=Expense::get();
        }

       
        return view('master.library-masters',compact('branches','plans','hours','plantype','planprice','plantypes','seat_button','expenses','is_extendday','notleaner'));
    }
    
    public function storemaster(Request $request, $id = null)
    {
        
        $this->validationfunction($request);
        $modelClass = 'App\\Models\\' . $request->databasemodel;
        $table=$request->databasetable;
        $data=$request->all();
        $plan_type_name=null;
       
        if ($request->databasemodel == 'Plan'){
            $data['name']=$request->plan_id .' '.$request->type;
        }
        if ($request->databasemodel == 'PlanType'){
            $data = $request->except(['timming']);

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
           }elseif($request->day_type_id==0){
            $plan_type_name=$request->custom_plan_type;
           }

            $data['name'] = $plan_type_name;
          
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
          
            if($request->databasemodel){
                if (is_null($data['id'])) {
                       
                    $modelInstance = $modelClass::create($data);
                } else {
                    
                    $modelInstance = $modelClass::findOrFail($data['id']);
                    
                    $modelInstance->update($data);
                }
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
           $modelClass = 'App\\Models\\' . $request->modeltable;
            $data=$modelClass::findOrFail($id);

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
      
        $data = PlanType::withTrashed()->get(); 

        return view('master.plantype-list', compact('data'));
    }
   public function planTypeCreate($id = null)
    {
        $planType = null;
        if ($id) {
            $planType = PlanType::find($id);  // Load existing record for edit
            if (!$planType) {
                return redirect()->route('planType.create')->with('error', 'Plan type not found.');
            }
        }
        // Pass $planType to view, it will be null for add and model instance for edit
        return view('master.plantype', compact('planType'));
    }

    public function planView()
    {
      
        $data = Plan::withTrashed()->get(); 

        return view('master.planlist', compact('data'));
    }
    public function planCreate($id = null)
    {
        $plan = null;
        if ($id) {
            $plan = Plan::find($id);  
            if (!$plan) {
                return redirect()->route('plan.create')->with('error', 'Plan not found.');
            }
        }
       
        return view('master.plan', compact('plan'));
    }

     public function expenseView()
    {
      
        $data = Expense::get(); 

        return view('master.expenselist', compact('data'));
    }
    public function expenseCreate($id = null)
    {
        $expense = null;
        if ($id) {
            $expense = Expense::find($id); 
            if (!$expense) {
                return redirect()->route('expense.create')->with('error', 'Expense not found.');
            }
        }
       
        return view('master.expense', compact('expense'));
    }

    public function seatCreate($id = null)
    {
       
        $seats = null;
        if ($id) {
            $seats =DB::table('hour')->where('branch_id',$id)->first();  
              
            if (!$seats) {
                return redirect()->route('branch.list')->with('error', 'Seats not found.');
            }
        }
      
        return view('master.seat-total', compact('seats'));
    }
     public function hourCreate($id = null)
    {
       
        $hour = null;
        if ($id) {
            $hour =DB::table('hour')->where('branch_id',$id)->first();  
              
            if (!$hour) {
                return redirect()->route('branch.list')->with('error', 'Hour not found.');
            }
        }
      
        return view('master.hour-total', compact('hour'));
    }
     public function extendDayCreate($id = null)
    {
        $extend = null;
        if ($id) {
            $extend = Branch::find($id);  
            if (!$extend) {
                return redirect()->route('branch.list')->with('error', 'Branch not found.');
            }
        }
       
        return view('master.extend-day', compact('extend'));
    }
    public function lockerAmountCreate($id = null)
    {
        $locker_amount = null;
        if ($id) {
            $locker_amount = Branch::find($id);  
            if (!$locker_amount) {
                return redirect()->route('branch.list')->with('error', 'Branch not found.');
            }
        }
       
        return view('master.locker-amount', compact('locker_amount'));
    }
        public function examView()
    {
      
        $data = DB::table('exams')->get(); 

        return view('master.examlist', compact('data'));
    }
    public function examCreate($id = null)
    {
        $exam = null;
        if ($id) {
            $exam = DB::table('exams')->find($id); 
            if (!$exam) {
                return redirect()->route('exam.create')->with('error', 'Exam not found.');
            }
        }
       
        return view('master.exam', compact('exam'));
    }
    

    public function activeDeactive(Request $request, $id)
    {
      
        $modelClass = 'App\\Models\\' . $request->dataTable;

        if (!class_exists($modelClass)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid model'], 400);
        }
       
        if ($request->dataTable == 'Hour') {
            $hour = Hour::find($id);
            if ($hour) {
                $hour->update(['extend_days' => null]);
                return response()->json(['status' => 'success', 'message' => 'Hour successfully updated', 'data_status' => 'updated']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Hour not found'], 404);
            }
        } else {
            $data = $modelClass::withTrashed()->find($id);

            if ($data) {
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
        $modelClass = 'App\\Models\\' . $table;
      

        if (!class_exists($modelClass)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid model'], 400);
        }
         $data = $modelClass::withTrashed()->find($id);

        if ($data) {
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
        }else{
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
                ->where('library_id', $request->library_id);

        }elseif($request->databasemodel == 'PlanPrice'){
            $query = $modelClass::where('plan_id', $request->plan_id)->where('plan_type_id',$request->plan_type_id)
            ->where('library_id', $request->library_id)->where('branch_id', $request->branch_id);
            
        }elseif($request->databasemodel=='Hour'){
            
            $query =DB::table('hour')->where('branch_id', $request->branch_id);
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
                'plan_id' => 'required|integer',
            ]);
        }
       
        if($request->databasemodel == 'PlanType'){
            $request->validate([
                'day_type_id' => 'required',
                'start_time' => 'required',
                'end_time' => 'required',
                'slot_hours' => 'required', 
            ]);
        }
        if($request->databasemodel == 'PlanPrice'){
            $request->validate([
                'plan_id' => 'required',
                'plan_type_id' => 'required',
                'price' => 'required',
                'branch_id' => ['required','not_in:0'],
            ]);
        }
        if($request->databasetable == 'hour'){
            $request->validate([
                'hour' => 'required|integer',
            ]);
        }
        if($request->databasetable == 'seats'){
            $request->validate([
                'total_seats' => 'required|integer',
            ]);
            
        }
        if ($request->databasemodel == 'Expense'){
            $request->validate([
                'name' => 'required|unique:expenses,name',
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
            $imageName = "icon" . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/icon/'), $imageName);
            $data['image'] = 'uploads/icon/' . $imageName;
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
        Log::info("No request");

        $query = $request->input('query');
        $suggestion = $request->input('suggestion');
        $city = $request->input('city');

        Log::info("request", ['query' => $query, 'suggestion' => $suggestion, 'city' => $city]);

        $libraries = DB::table('branches as b')
            ->join('libraries as l', 'l.id', '=', 'b.library_id')
            ->leftJoin('hour as h', 'h.branch_id', '=', 'b.id')
            ->where('l.is_paid', 1)
            ->where('b.is_profile', 1)
            ->select(
                'b.id as id',
                'b.library_id',
                'b.library_address',
                'b.name as library_name',
                'b.google_map',
                'b.state_id',
                'b.city_id',
                'b.library_logo',
                'b.slug',
                'h.seats'
            );

        // Apply filters
        if ($suggestion) {
            $libraries->where(function ($q) use ($suggestion) {
                $q->where('b.name', 'like', "%$suggestion%")
                ->orWhere('b.library_address', 'like', "%$suggestion%");
            });
        } elseif ($query) {
            $libraries->where(function ($q) use ($query) {
                $q->where('b.name', 'like', "%$query%")
                ->orWhere('b.library_address', 'like', "%$query%");
            });
        } elseif ($city) {
            $libraries->where('b.city_id', $city);
        } else {
            $libraries->take(5);
        }

        $results = $libraries->get();

        // Fallback for completely empty search
        if ($results->isEmpty() && !$query && !$suggestion && !$city) {
            $results = DB::table('branches as b')
                ->join('libraries as l', 'l.id', '=', 'b.library_id')
                ->leftJoin('hour as h', 'h.branch_id', '=', 'b.id')
                ->where('l.is_paid', 1)
                ->where('b.is_profile', 1)
                ->inRandomOrder()
                ->take(5)
                ->select(
                    'b.id as id',
                    'b.library_id',
                    'b.library_address',
                    'b.name as library_name',
                    'b.google_map',
                    'b.state_id',
                    'b.city_id',
                    'b.library_logo',
                    'b.slug',
                    'h.seats'
                )
                ->get();
        }

    
        return response()->json($results);
    }

    public function planPriceView()
    {
        
        if(getCurrentBranch() !=0){
            $data = PlanPrice::with([
            'plan' => function ($query) {
                $query->withTrashed();
            },
            'planType' => function ($query) {
                $query->withTrashed();
            }
        ])->withTrashed()->get();

        }else{
            $data =[];
        }
        
        
        return view('master.planPricelist', compact('data'));
    }
    public function planPriceCreate($id = null)
    {
        $planPrice = null;
        if ($id) {
            $planPrice = PlanPrice::find($id);  
            if (!$planPrice) {
                return redirect()->route('plan.create')->with('error', 'Plan not found.');
            }
        }
        $plantypes =PlanType::get();       
        return view('master.planPrice', compact('planPrice','plantypes'));
    }

    public function menu(){
        return view('master.menu');
    }
    
}
