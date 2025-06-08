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
                })
                ->get();
        }


         

         $subscriptions = Subscription::where('id', Auth::user()->library_type)->get();
        $permissions = $subscriptions->flatMap(function ($subscription) {
            return $subscription->permissions->pluck('id', 'name');
        })->unique();


         $branches=Branch::where('library_id',getLibraryId())->get();
         foreach ($users as $user) {
            $user->branch_names = Branch::whereIn('id', $user->branch_id)->pluck('name')->toArray();
            $user->permissions_array = $user->permissions->pluck('name')->toArray();
        }
     
         return view('library_users.index', compact('users', 'permissions','branches'));
     }
     
    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:library_users,email,' . $request->id,
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
            'branch_id' => 'required|array|min:1',
            'status' => 'required|in:0,1',
            'permissions' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
       
        try {
            $data = $request->only('name', 'email', 'mobile', 'status');

            if ($request->filled('branch_id')) {
                $data['branch_id'] = $request->branch_id;
            }

            $data['library_id'] = auth()->guard('library')->id();

            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            }
            $data['original_password']=$request->password;
            // Create or update LibraryUser
            $user = LibraryUser::updateOrCreate(['id' => $request->id], $data);

           $user->permissions()->sync($request->permissions); // pivot table

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User saved successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error saving library user: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }
        

     
     
     public function toggleStatus($id)
     {
         $user = LibraryUser::findOrFail($id);
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
