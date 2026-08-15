<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LibraryUser;
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
       if (getCurrentBranch() == 0) {
            $users = LibraryUser::where('library_id', getLibraryId())->get();
        } else {
            $currentBranches = (array) getCurrentBranch(); // force to array

            $users = LibraryUser::where('library_id', getLibraryId())
                ->where(function($query) use ($currentBranches) {
                    foreach ($currentBranches as $branchId) {
                        $query->orWhereJsonContains('branch_id', (string) $branchId); // cast to string if JSON stores values as strings
                    }
                })->with('permissions')
                ->get();
        }

         foreach ($users as $user) {
            $user->branch_names = Branch::whereIn('id', $user->branch_id)->pluck('name')->toArray();
            $user->permissions_array = $user->permissions->pluck('name')->toArray();
        }
        $subscription = Subscription::find(auth()->user()->library_type);
        $groupedPermissions = collect();

        if ($subscription) {
            $permissions = $subscription->permissions()->get();
            $groupedPermissions = $permissions->groupBy('permission_category_id')->map(fn($g) => $g->pluck('id', 'name'));
        }

        $total_user=$users->count();
        $active_user=$users->where('status',1)->count();
        $inactive_user=$users->where('status',0)->count();
        
         return view('library_users.index', compact('users','groupedPermissions','total_user','active_user','inactive_user'));
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


         $branches = Branch::where('library_id', getLibraryId())->get();
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
            'role' => 'required|string', 
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ],
        [
            'branch.required' => 'Please select at least one branch.',
            'branch.min' => 'Please select at least one branch.',
            'branch.*.in' => 'The selected branch is invalid or inactive.',
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

            return response()->json([
                'success' => true,
                  'redirect'=>route('library-users.index'),
                'message' => 'User saved successfully.',
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
        $user = LibraryUser::with('permissions')->where('library_id', getLibraryId())->findOrFail($id);

        $subscription = Subscription::find(auth()->user()->library_type);
        $groupedPermissions = collect();

        if ($subscription) {
            $permissions = $subscription->permissions()->get();
            $groupedPermissions = $permissions->groupBy('permission_category_id')->map(fn($g) => $g->pluck('id', 'name'));
        }

        return view('library_users.permissions', compact('user', 'groupedPermissions'));
    }

    public function updatePermissions(Request $request, $id)
    {
        $user = LibraryUser::where('library_id', getLibraryId())->findOrFail($id);
        $user->permissions()->sync($request->permissions); 
    
        return redirect()->route('library-users.index')->with('success', 'Permissions updated successfully.');
    }


     
     
     public function toggleStatus($id)
     {
         $user = LibraryUser::where('library_id', getLibraryId())->findOrFail($id);
         $user->status = !$user->status;
         $user->save();
     
         return response()->json(['message' => 'Status updated.']);
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
