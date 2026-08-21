<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    // ==========================================
    // USERS MANAGEMENT (Web Guard & admin_user_permissions Table)
    // ==========================================

    public function usersIndex()
    {
        $users = User::orderBy('id', 'desc')->paginate(15);
        return view('administrator.users.index', compact('users'));
    }

    public function usersCreate()
    {
        $roles = Role::where('guard_name', 'web')->get();
        $permissions = Permission::where('guard_name', 'web')->get()->groupBy(function($item) {
            return explode('-', $item->name)[0] ?? 'General';
        });
        return view('administrator.users.form', compact('roles', 'permissions'));
    }

    public function usersEdit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::where('guard_name', 'web')->get();
        $userRole = $user->roles->first() ? $user->roles->first()->name : '';
        $permissions = Permission::where('guard_name', 'web')->get()->groupBy(function($item) {
            return explode('-', $item->name)[0] ?? 'General';
        });
        $userPermissions = DB::table('admin_user_permissions')
            ->join('permissions', 'permissions.id', '=', 'admin_user_permissions.permission_id')
            ->where('admin_user_permissions.user_id', $user->id)
            ->pluck('permissions.name')
            ->toArray();

        return view('administrator.users.form', compact('user', 'roles', 'userRole', 'permissions', 'userPermissions'));
    }

    public function usersStore(Request $request, $id = null)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'role'     => 'nullable|string',
            'password' => $id ? 'nullable|min:6' : 'required|min:6',
        ]);

        if ($id) {
            $user = User::findOrFail($id);
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                if (Schema::hasColumn('users', 'original_password')) {
                    $user->original_password = $request->password;
                }
            }
            if ($request->has('status') && Schema::hasColumn('users', 'status')) {
                $user->status = $request->status;
            }
            $user->save();

            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
            }
            $msg = 'User updated successfully.';
        } else {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            if (Schema::hasColumn('users', 'original_password')) {
                $user->original_password = $request->password;
            }
            if (Schema::hasColumn('users', 'status')) {
                $user->status = $request->status ?? 1;
            }
            $user->save();

            if ($request->filled('role')) {
                $user->assignRole($request->role);
            }
            $msg = 'User created successfully.';
        }

        // Sync permissions in admin_user_permissions table
        DB::table('admin_user_permissions')->where('user_id', $user->id)->delete();
        if ($request->has('permissions')) {
            $permIds = Permission::where('guard_name', 'web')->whereIn('name', $request->permissions)->pluck('id');
            foreach ($permIds as $pId) {
                DB::table('admin_user_permissions')->insert([
                    'user_id' => $user->id,
                    'permission_id' => $pId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return redirect()->route('admin.users')->with('success', $msg);
    }

    public function usersToggleStatus($id)
    {
        $user = User::findOrFail($id);
        if (Schema::hasColumn('users', 'status')) {
            $user->status = ($user->status ?? 1) == 1 ? 0 : 1;
            $user->save();
        }
        return redirect()->back()->with('success', 'User status updated.');
    }

    public function usersDestroy($id)
    {
        $user = User::findOrFail($id);
        DB::table('admin_user_permissions')->where('user_id', $user->id)->delete();
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    // ==========================================
    // ROLES MANAGEMENT (Web Guard Only)
    // ==========================================

    public function rolesIndex()
    {
        $roles = Role::where('guard_name', 'web')->with('permissions')->orderBy('id', 'desc')->get();
        return view('administrator.roles.index', compact('roles'));
    }

    public function rolesCreate()
    {
        $permissions = Permission::where('guard_name', 'web')->get()->groupBy(function($item) {
            return explode('-', $item->name)[0] ?? 'General';
        });
        return view('administrator.roles.form', compact('permissions'));
    }

    public function rolesEdit($id)
    {
        $role = Role::where('guard_name', 'web')->findOrFail($id);
        $permissions = Permission::where('guard_name', 'web')->get()->groupBy(function($item) {
            return explode('-', $item->name)[0] ?? 'General';
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('administrator.roles.form', compact('role', 'permissions', 'rolePermissions'));
    }

    public function rolesStore(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ]);

        if ($id) {
            $role = Role::where('guard_name', 'web')->findOrFail($id);
            $role->name = $request->name;
            $role->save();
            $msg = 'Role updated successfully.';
        } else {
            $role = Role::create([
                'name'       => $request->name,
                'guard_name' => 'web',
            ]);
            $msg = 'Role created successfully.';
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('admin.roles')->with('success', $msg);
    }

    public function rolesDestroy($id)
    {
        $role = Role::where('guard_name', 'web')->findOrFail($id);
        $role->delete();
        return redirect()->route('admin.roles')->with('success', 'Role deleted successfully.');
    }

    // ==========================================
    // PERMISSIONS MANAGEMENT & UNIFORM CRUD (Web Guard Only)
    // ==========================================

    public function permissionsIndex(Request $request)
    {
        $query = Permission::where('guard_name', 'web');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $permissions = $query->orderBy('id', 'asc')->paginate(20);
        $roles = Role::where('guard_name', 'web')->with('permissions')->get();

        return view('administrator.permissions.index', compact('permissions', 'roles'));
    }

    public function permissionsCreate()
    {
        return view('administrator.permissions.form');
    }

    public function permissionsEdit($id)
    {
        $permission = Permission::where('guard_name', 'web')->findOrFail($id);
        return view('administrator.permissions.form', compact('permission'));
    }

    public function permissionsStore(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
        ]);

        $formattedName = strtolower(trim(str_replace(' ', '-', $request->name)));

        if ($id) {
            $permission = Permission::where('guard_name', 'web')->findOrFail($id);
            $permission->name = $formattedName;
            $permission->save();
            $msg = 'Permission updated successfully.';
        } else {
            Permission::create([
                'name'       => $formattedName,
                'guard_name' => 'web',
            ]);
            $msg = 'Permission created successfully.';
        }

        return redirect()->route('admin-permissions')->with('success', $msg);
    }

    public function permissionsAssign(Request $request)
    {
        $roleId = $request->role_id;
        $role = Role::where('guard_name', 'web')->findOrFail($roleId);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->back()->with('success', 'Role permissions updated successfully.');
    }

    public function permissionsDestroy($id)
    {
        $permission = Permission::where('guard_name', 'web')->findOrFail($id);
        DB::table('admin_user_permissions')->where('permission_id', $permission->id)->delete();
        $permission->delete();
        return redirect()->route('admin-permissions')->with('success', 'Permission deleted successfully.');
    }
}
