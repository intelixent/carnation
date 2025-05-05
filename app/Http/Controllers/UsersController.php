<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends BaseController
{
    protected $isSuperAdmin;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);

        // Only apply permission middleware for non-superadmin users
        if (!$this->isSuperAdmin) {
            $this->middleware('permissions:create-user')->only(['user_add', 'user_store']);
            $this->middleware('permissions:list-user')->only(['user_index']);
            $this->middleware('permissions:view-user')->only(['get_user_details']);
            $this->middleware('permissions:edit-user')->only(['user_edit', 'user_update']);
            $this->middleware('permissions:delete-user')->only('user_delete');
            $this->middleware('permissions:create-role')->only(['role_add', 'role_store']);
            $this->middleware('permissions:list-role')->only(['role_index']);
            $this->middleware('permissions:view-role')->only(['get_role_details']);
            $this->middleware('permissions:edit-role')->only(['role_edit', 'role_update']);
            $this->middleware('permissions:delete-role')->only('role_delete');
        }
    }

    public function user_index()
    {
        $page_data = [
            'page_title' => "User Master",
            'page_main_title' => "User",
            'isSuperAdmin' => $this->isSuperAdmin,
            'users' => User::with('roles')->latest()->get(),
        ];

        return view('users.user.index', $page_data);
    }

    public function user_add()
    {
        $page_data['permissions']  = Permission::all()->groupBy('category');

        $page_data['page_title'] = "User";
        $page_data['page_main_title'] = "User";

        $page_data['roles'] = Role::all();

        return view('users.user.add', $page_data);
    }

    public function user_store(Request $request)
    {
        $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'
            ],
            'email' => 'required|string|email|max:255|unique:users',
            'username' => [
                'required',
                'string',
                'max:255',
                'unique:users',
                'lowercase'
            ],
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
            'mobile' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/'
            ],
            'designation' => 'required|string|max:255',
            'role' => 'required',
            'permissions' => 'array',
            'address' => 'required|string|max:500',
        ], [
            'first_name.regex' => 'First name should only contain letters and single spaces between words',
            'last_name.regex' => 'Last name should only contain letters and single spaces between words',
            'mobile.regex' => 'Please enter a valid 10-digit mobile number starting with 6-9',
            'password.confirmed' => 'Password confirmation does not match',
            'username.lowercase' => 'Username must be in lowercase'
        ]);

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'username' => strtolower($request->username),
                'password' => Hash::make($request->password),
                'mobile' => $request->mobile,
                'designation' => $request->designation,
                'address' => $request->address,
            ]);

            $role = Role::findById($request->role);
            $user->assignRole($role);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $user->syncPermissions($permissions);
            }

            return redirect()->route('user_index')->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while creating the user: ' . $e->getMessage())
                ->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    public function user_edit($id)
    {
        $user = User::with(['roles', 'permissions'])->findOrFail($id);

        $roles = Role::all();
        $permissions = $user->roles->flatMap->permissions->groupBy('category');

        return view('users.user.edit', [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
            'page_title' => 'Edit User',
            'page_main_title' => 'User',
        ]);
    }

    public function user_update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validationRules = [
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'mobile' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('users')->ignore($user->id),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'required|exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'address' => 'required|string|max:500',
        ];

        if ($request->filled('password')) {
            $validationRules['password'] = 'required|min:8|confirmed';
        }

        $request->validate($validationRules);

        try {
            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'username' => $request->username,
                'address' => $request->address,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            $role = Role::findById($request->role);
            $user->syncRoles([$role]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $user->syncPermissions($permissions);
            }

            return redirect()->route('user_index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while updating the user.')
                ->withInput();
        }
    }

    public function user_delete(Request $request)
    {
        try {
            $user = User::find($request->id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ]);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while deleting user',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function get_user_details(Request $request)
    {
        $user = User::with(['roles.permissions'])->findOrFail($request->id);

        return view('users.user.details', compact('user'));
    }

    public function get_user_password(Request $request)
    {
        $user = User::findOrFail($request->id);

        return view('users.user.password', compact('user'));
    }

    public function user_password_update(Request $request)
    {
        $user = User::findOrFail($request->user_id);

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User password updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the user password.'
            ], 500);
        }
    }

    public function role_index()
    {
        $page_data = [
            'page_title' => "Roles Master",
            'page_main_title' => "User",
            'isSuperAdmin' => $this->isSuperAdmin,
            'roles' => Role::all(),
        ];

        return view('users.role.index', $page_data);
    }

    public function role_add()
    {
        $page_data['permissions']  = Permission::all()->groupBy('category');

        $page_data['page_title'] = "Roles";
        $page_data['page_main_title'] = "User";

        return view('users.role.add', $page_data);
    }

    public function role_store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        try {
            $role = Role::create(['name' => $request->name]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            }

            return redirect()->route('role_index')
                ->with('success', 'Role created successfully with selected permissions.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while creating the role: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function role_edit($id)
    {
        $page_data['role'] = Role::findOrFail($id);
        $page_data['permissions'] = Permission::all()->groupBy('category');
        $page_data['rolePermissions'] = $page_data['role']->permissions->pluck('id')->toArray();

        $page_data['page_title'] = "Edit Role";
        $page_data['page_main_title'] = "User";

        return view('users.role.edit', $page_data);
    }

    public function role_update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', Rule::unique('roles')->ignore($id)],
            'permissions' => 'array',
        ]);

        try {
            $role = Role::findOrFail($id);
            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->permissions)->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            return redirect()->route('role_index')
                ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while updating the role: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function get_role_details(Request $request)
    {
        $role = Role::with('permissions')->findOrFail($request->id);
        $permissions = Permission::all();

        return view('users.role.details', compact('role', 'permissions'));
    }

    public function get_role_permissions($roleId)
    {
        $role = Role::findById($roleId);
        $permissions = $role->permissions->groupBy('category');

        return response()->json($permissions);
    }
}
