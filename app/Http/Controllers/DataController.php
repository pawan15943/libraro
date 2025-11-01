<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\Grade;
use App\Models\Menu;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\State;
use App\Models\SubMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerFeedback;
use App\Models\LearnerTransactionActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
   
    public function create ()
    {
       
        $menusParent=Menu::pluck('name','id');
        $menus=Menu::withTrashed()->get();
       
        $permissions=Permission::where('permission_category_id',2)->select('name')->get();
       
        return view('master.menu',compact('menus','menusParent','permissions'));
    }
    public function edit($id)
    {
        $menus=Menu::withTrashed()->get();
        $menu=Menu::find($id);
        return view('master.menu', compact('menu','menus'));
    }
    public function store(Request $request)
    {
        $request->validate([
        'name' => 'required',
        'url' => 'required',
        'order' => 'required',
        'guard' => 'required',
       
        ]);
        
        Menu::create(
           $request->all()
        );
        return redirect()->back()->with('success', 'Data created successfully.');
    }
    public function update(Request $request, Menu $menu)
    {
        $validatedData = $request->validate([
            'id' => 'required|exists:menus,id',
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'order' => 'required|integer',
            'guard' => 'required|string|max:255',
           'parent_id'=>'nullable',
           'has_permissions'=>'nullable',
           
            'icon' => 'nullable|string|max:255',
        ]);

        $menu = Menu::find($validatedData['id']);

        // Update the menu with the new data
        try {
            $update = $menu->update($validatedData);
            return redirect()->route('menu.create')->with('success', 'Menu updated successfully.');
        } catch (\Exception $e) {
            Log::error('Update Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update the menu.');
        }

 
    }

    public function submenu_index(Request $request)
    {
       
        $submenus=SubMenu::leftJoin('menus','menus.id','=','sub_menus.parent_id')->select('sub_menus.*','menus.name as menu_name')->withTrashed()->get();
        return view('master.submenu',compact('submenus'));
    }
    public function submenu_create ()
    {
        $menu_list=Menu::all();
        $menus=SubMenu::all();
        $submenus=SubMenu::leftJoin('menus','menus.id','=','sub_menus.parent_id')->select('sub_menus.*','menus.name as menu_name')->withTrashed()->get();

        return view('master.submenu',compact('menus','menu_list','submenus'));
    }
    public function submenu_edit($id)
    {
        $menu_list=Menu::withTrashed()->get();
    
        $menu=SubMenu::find($id);
        $submenus=SubMenu::leftJoin('menus','menus.id','=','sub_menus.parent_id')->select('sub_menus.*','menus.name as menu_name')->withTrashed()->get();

        return view('master.submenu', compact('menu','menu_list','submenus'));
    }
    public function submenu_store(Request $request)
    {
        $request->validate([
        'name' => 'required',
        'order' => 'required',
        'slug' => 'required',
        'url' => 'required',
        'parent_id' => 'required',
        ]);
        
        SubMenu::create(
           $request->all()
        );
        return redirect()->back()->with('success', 'Data created successfully.');
    }
    

    public function submenu_update(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'order' => 'required|integer',
            'slug' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'parent_id' => 'required|integer',
            'icon' => 'nullable|string|max:255',
        ]);

        // Find the SubMenu instance by ID
        $submenu = SubMenu::find($request->id);

        // Check if the SubMenu instance is found
        if (!$submenu) {
            return redirect()->back()->with('error', 'SubMenu not found.');
        }

        // Log the submenu before update
        Log::info('SubMenu Before Update: ', $submenu ? $submenu->toArray() : 'SubMenu not found');

        // Update the submenu with the new data
        try {
            $update = $submenu->update($validatedData);

            // Log the submenu after update
            Log::info('SubMenu After Update: ', $submenu->fresh() ? $submenu->fresh()->toArray() : 'SubMenu not found after update');
        } catch (\Exception $e) {
            Log::error('Update Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update the submenu.');
        }

        if ($update) {
            return redirect()->route('submenu.create')->with('success', 'SubMenu updated successfully.');
        } else {
            return redirect()->back()->with('error', 'SubMenu not updated successfully.');
        }
    }

    // Redirect back with a success message
    
    public function activeDeactive(Request $request,$id)
    {
        
        $modelClass = 'App\\Models\\' . $request->dataTable; // Dynamically build the model class name

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
            
                return response()->json(['status' => 'success', 'message' => 'Data successfully ' . $status, 'data_status' => $status]);
            }else{
                
            } return response()->json(['status' => 'error', 'message' => 'Data not found'], 404);
        
    }

    public function updateLearnerStatus()
    {
        $today = Carbon::today();
        $futureCheckDate = $today->copy()->addDays(5);

        $customerdatas = LearnerDetail::where('status', 1)->get();

        foreach ($customerdatas as $customerdata) {
            $branchId = $customerdata->branch_id;
            $branch = $branchId ? Branch::find($branchId) : null;
            $extend_day = $branch ? $branch->extend_days : 0;

            $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);

            $hasFuturePlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '>', $futureCheckDate)
                ->where('status', 0)
                ->exists();

            $hasPastPlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '<', $futureCheckDate)
                ->exists();

            $isRenewed = $hasFuturePlan && $hasPastPlan;

            if ($planEndDateWithExtension->lte($today)) {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 0)
                    ->update(['status' => 0]);

                $customerdata->update(['status' => 0]);
            } elseif ($isRenewed) {
                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $futureCheckDate)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_end_date', '<', $today)
                    ->update(['status' => 0]);
            } else {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 1)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('status', 0)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $today)
                    ->update(['status' => 1]);
            }
            
        }

        
    }

    public function deleteExpiredLearners()
    {
        $branches = Branch::whereNotNull('expired_learner_delete')
            ->select('id', 'expired_learner_delete')
            ->get();

        $totalDeleted = 0;

        foreach ($branches as $branche) {
            $cutoffDate = Carbon::now()->subDays($branche->expired_learner_delete);

            // Get learners whose latest plan_end_date < cutoffDate and all statuses = 0
            $expiredLearners = Learner::select('learners.id')
                ->join('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->where('learners.branch_id', $branche->id)
                ->where('learners.status', 0)
                ->where('learner_detail.status', 0)
                ->groupBy('learner_detail.learner_id')
                ->havingRaw('MAX(learner_detail.plan_end_date) < ?', [$cutoffDate])
                ->pluck('learners.id');

            // Delete in transaction
            DB::transaction(function () use ($expiredLearners, &$totalDeleted) {

            if ($expiredLearners->isNotEmpty()) {

                // Remove learner_id in learner transaction table to avoid affecting revenue
                LearnerTransactionActivity::whereIn('learner_id', $expiredLearners)->update([
                    'learner_id' => null
                ]);
                LearnerFeedback::whereIn('learner_id', $expiredLearners)->update([
                    'learner_id' => null
                ]);
                

                // Then permanently delete learners
                Learner::whereIn('id', $expiredLearners)->forceDelete();

                $totalDeleted += $expiredLearners->count();
            }

        });

        }

        return response()->json([
            'message' => $totalDeleted > 0
                ? "$totalDeleted learners permanently deleted successfully."
                : "No expired learners found.",
            'deleted_count' => $totalDeleted
        ]);
    }

   

}
