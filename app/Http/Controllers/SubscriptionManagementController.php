<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\PermissionCategory;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementController extends Controller
{
    // ==========================================
    // SUBSCRIPTIONS LIST VIEW
    // ==========================================

    public function subscriptionsIndex(Request $request)
    {
        $query = Subscription::withTrashed()->with('permissions');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $subscriptions = $query->orderBy('id', 'desc')->paginate(15);
        return view('administrator.subscriptions.index', compact('subscriptions'));
    }

    // ==========================================
    // CREATE SUBSCRIPTION FORM
    // ==========================================

    public function subscriptionsCreate()
    {
        // Fetch library permissions grouped strictly by Permission Category
        $categories = PermissionCategory::with(['permissions' => function($q) {
            $q->where('guard_name', 'library')->orderBy('name', 'asc');
        }])->get();

        $uncategorized = Permission::where('guard_name', 'library')
            ->where(function($q) {
                $q->whereNull('permission_category_id')->orWhere('permission_category_id', 0);
            })->orderBy('name', 'asc')->get();

        return view('administrator.subscriptions.form', compact('categories', 'uncategorized'));
    }

    // ==========================================
    // EDIT SUBSCRIPTION FORM
    // ==========================================

    public function subscriptionsEdit($id)
    {
        $subscription = Subscription::withTrashed()->findOrFail($id);

        // Fetch library permissions grouped strictly by Permission Category
        $categories = PermissionCategory::with(['permissions' => function($q) {
            $q->where('guard_name', 'library')->orderBy('name', 'asc');
        }])->get();

        $uncategorized = Permission::where('guard_name', 'library')
            ->where(function($q) {
                $q->whereNull('permission_category_id')->orWhere('permission_category_id', 0);
            })->orderBy('name', 'asc')->get();

        $assignedPermissions = $subscription->permissions()->pluck('permissions.id')->toArray();

        return view('administrator.subscriptions.form', compact('subscription', 'categories', 'uncategorized', 'assignedPermissions'));
    }

    // ==========================================
    // STORE / UPDATE SUBSCRIPTION WITH PERMISSIONS
    // ==========================================

    public function subscriptionsStore(Request $request, $id = null)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'monthly_fees'          => 'required|numeric|min:0',
            'yearly_fees'           => 'nullable|numeric|min:0',
            'three_monthly_fees'    => 'nullable|numeric|min:0',
            'six_monthly_fees'      => 'nullable|numeric|min:0',
            'two_yearly_fees'       => 'nullable|numeric|min:0',
            'max_seats'             => 'nullable|integer|min:0',
            'max_branches'          => 'nullable|integer|min:0',
            'plan_description'      => 'nullable|string',
        ]);

        $data = [
            'name'               => $request->name,
            'monthly_fees'       => $request->monthly_fees,
            'yearly_fees'        => $request->yearly_fees ?? 0,
            'three_monthly_fees' => $request->three_monthly_fees ?? 0,
            'six_monthly_fees'   => $request->six_monthly_fees ?? 0,
            'two_yearly_fees'    => $request->two_yearly_fees ?? 0,
            'max_seats'          => $request->max_seats,
            'max_branches'       => $request->max_branches,
            'plan_description'   => $request->plan_description,
        ];

        if ($id) {
            $subscription = Subscription::withTrashed()->findOrFail($id);
            $subscription->update($data);
            $msg = 'Subscription plan updated successfully.';
        } else {
            $subscription = Subscription::create($data);
            $msg = 'Subscription plan created successfully.';
        }

        // Sync selected permissions in pivot table
        if ($request->has('permissions')) {
            $subscription->permissions()->sync($request->permissions);
        } else {
            $subscription->permissions()->detach();
        }

        return redirect()->route('admin.subscriptions')->with('success', $msg);
    }

    // ==========================================
    // TOGGLE ACTIVE / INACTIVE STATUS
    // ==========================================

    public function subscriptionsToggleStatus($id)
    {
        $subscription = Subscription::withTrashed()->findOrFail($id);
        if ($subscription->trashed()) {
            $subscription->restore();
            $msg = 'Subscription plan activated.';
        } else {
            $subscription->delete();
            $msg = 'Subscription plan deactivated.';
        }

        return redirect()->back()->with('success', $msg);
    }

    // ==========================================
    // DELETE SUBSCRIPTION PLAN
    // ==========================================

    public function subscriptionsDestroy($id)
    {
        $subscription = Subscription::withTrashed()->findOrFail($id);
        $subscription->permissions()->detach();
        $subscription->forceDelete();

        return redirect()->route('admin.subscriptions')->with('success', 'Subscription plan deleted permanently.');
    }
}
