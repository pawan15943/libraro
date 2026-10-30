<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LibraryUser;
use App\Models\PermissionCategory;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class LibraryUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index()
     {
        // Owners manage the whole team, whichever branch is open right now; each card shows its branches
        $users = LibraryUser::where('library_id', getLibraryId())
            ->with(['permissions:id,name,permission_category_id', 'roles:id,name'])
            ->orderByDesc('status')->orderBy('name')->get();

        // Branch names and the plan's permissions are loaded once for every card
        $branchNames = Branch::where('library_id', getLibraryId())->pluck('name', 'id');
        $planPermissions = $this->planPermissions();
        $planPermissionIds = $planPermissions->pluck('id')->all();

        foreach ($users as $user) {
            $user->branch_names = collect((array) $user->branch_id)->map(fn ($id) => $branchNames[$id] ?? null)->filter()->values()->all();
            $granted = $user->permissions->whereIn('id', $planPermissionIds);
            $user->access_count = $granted->count();
            $user->access_by_category = $granted->groupBy('permission_category_id')->map->count();
        }

        $categoryNames = PermissionCategory::pluck('name', 'id');
        $total_user = $users->count();
        $active_user = $users->where('status', 1)->count();
        $inactive_user = $users->where('status', 0)->count();
        $plan_permission_count = count($planPermissionIds);

        return view('library_users.index', compact('users', 'categoryNames', 'total_user', 'active_user', 'inactive_user', 'plan_permission_count'));
     }

    /** Permissions included in the library's plan: the most a staff member can be given. */
    private function planPermissions()
    {
        $library = Auth::guard('library')->user() ?? Auth::user();
        $subscription = $library ? Subscription::find($library->library_type) : null;

        return $subscription ? $subscription->permissions()->get(['permissions.id', 'permissions.name', 'permissions.permission_category_id']) : collect();
    }

    public function create($id = null){
         $editUser = null;

        if ($id) {
            // ✅ Load roles as well for edit mode
            $editUser = LibraryUser::where('library_id', getLibraryId())->findOrFail($id);
            $editUser->load('roles');
        }

        $subscription = Subscription::find(Auth::user()->library_type);

        $groupedPermissions = collect();

        if ($subscription) {
            $permissions = $subscription->permissions()->get();

            $groupedPermissions = $permissions->groupBy('permission_category_id')->map(function ($group) {
                return $group->pluck('id', 'name');
            });
        }


         $branches = Branch::where('library_id', getLibraryId())->orderByDesc('status')->orderBy('name')->get();
         if ($branches->isEmpty()) {
             return redirect()->route('branch.create')->with('error', 'No branch found. Please create a branch first before adding users.');
         }

         $roles = Role::where('guard_name', 'library_user')->get();
         
         return view('library_users.create', compact('branches', 'groupedPermissions', 'editUser', 'roles'));
     }

    public function store(Request $request)
    {
        $activeBranches = Branch::where('library_id', getLibraryId())->where('status', 1)->pluck('id')->toArray();
        if (empty($activeBranches)) {
            return response()->json([
                'success' => false,
                'message' => 'No active branch found. Please create and activate a branch first before adding users.',
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:library_users,email,' . $request->id,
            'password' => $request->id ? 'nullable|min:6|confirmed' : 'required|min:6|confirmed',
            'password_confirmation' => $request->id 
            ? 'nullable|min:6' 
            : 'required|min:6',
            'branch' => 'required|array|min:1',
            'branch.*' => 'integer|in:' . implode(',', $activeBranches),
            'mobile' => 'required|digits:10',
            'role' => 'required|string|exists:roles,name,guard_name,library_user',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:1024',
        ],
        [
            'branch.required' => 'Please select at least one branch.',
            'branch.min' => 'Please select at least one branch.',
            'branch.*.in' => 'The selected branch is invalid or inactive.',
            'role.required' => 'Choose a role.',
            'role.exists' => 'Choose a role from the list.',
            'password.confirmed' => 'The two passwords do not match.',
        ]
        );
        

        DB::beginTransaction();

        try {
            // Guard against updating a staff account that belongs to another library
            if ($request->filled('id')) {
                LibraryUser::where('library_id', getLibraryId())->findOrFail($request->id);
            }

            $data = $request->only('name', 'email', 'mobile');

            if ($request->filled('branch')) {
                $data['branch_id'] = $request->branch;
            }
            // $data['library_id'] = auth()->guard('library')->id();

            $data['library_id'] = getLibraryId();

            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
                  $data['original_password']=$request->password;
            }
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $filePath = $file->store('user_profile_picture', 'public');
                $data['profile_picture'] = $filePath;
            }


            // Create or update LibraryUser
            $user = LibraryUser::updateOrCreate(['id' => $request->id], $data);

            if (!$user) {
                throw new \Exception('User creation failed');
            }

             // ✅ Assign role instead of permissions
            $user->syncRoles([$request->role]);
            //    $user->permissions()->sync($request->permissions); 


            DB::commit();

            // A new staff member has no access yet, so the next step is choosing it
            $isNew = !$request->filled('id');

            return response()->json([
                'success' => true,
                'redirect' => $isNew ? route('library-users.permissions', ['user' => $user->id, 'new' => 1]) : route('library-users.index'),
                'message' => $isNew ? $user->name . ' is added. Now choose what they can do.' : 'Staff details saved.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error saving library user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'redirect'=>route('library-users.index'),
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }
        
    public function editPermissions($id)
    {
        $user = LibraryUser::with('permissions:id')->where('library_id', getLibraryId())->findOrFail($id);

        $planPermissions = $this->planPermissions();
        $groupedPermissions = $planPermissions->groupBy('permission_category_id')->map(fn ($g) => $g->pluck('id', 'name'));
        $categoryNames = PermissionCategory::pluck('name', 'id');
        $planIds = $planPermissions->pluck('id');

        // Teammates whose access can be copied in one tap
        $teammates = LibraryUser::where('library_id', getLibraryId())->where('id', '!=', $user->id)
            ->with('permissions:id')->orderBy('name')->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'ids' => $t->permissions->pluck('id')->intersect($planIds)->values()])
            ->filter(fn ($t) => $t['ids']->isNotEmpty())->values();

        return view('library_users.permissions', compact('user', 'groupedPermissions', 'categoryNames', 'teammates'));
    }

    public function updatePermissions(Request $request, $id)
    {
        $user = LibraryUser::where('library_id', getLibraryId())->findOrFail($id);

        // Only permissions the library's plan includes can be given
        $allowed = $this->planPermissions()->pluck('id')->all();
        $ids = array_values(array_intersect(array_map('intval', (array) $request->input('permissions', [])), $allowed));
        $user->permissions()->sync($ids);

        return redirect()->route('library-users.index')->with('success', 'Access saved for ' . $user->name . '.');
    }

     public function toggleStatus($id)
     {
         $user = LibraryUser::where('library_id', getLibraryId())->findOrFail($id);
         $user->status = !$user->status;
         $user->save();

         return response()->json([
             'status' => (int) $user->status,
             'message' => $user->status ? $user->name . ' can sign in again.' : $user->name . ' can no longer sign in.',
         ]);
     }
     

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LibraryUser $libraryUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LibraryUser $libraryUser)
    {
        //
    }
}
