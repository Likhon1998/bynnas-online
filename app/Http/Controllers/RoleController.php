<?php

namespace App\Http\Controllers;

use App\Support\StaffPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Roles that cannot be edited from the admin to avoid locking the shop out. */
    private const LOCKED = ['Shop Owner', 'Customer'];

    /** Role names referenced in code; their permissions can change but not their names. */
    private const SYSTEM = ['Admin', 'Manager', 'Cashier'];

    public function index()
    {
        $roles = Role::with('permissions')->where('guard_name', 'web')->get();
        $visible = StaffPermissions::assignable()->pluck('name')->all();

        return view('roles.index', compact('roles', 'visible'));
    }

    public function create()
    {
        $permissions = StaffPermissions::assignable();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions']);

        return redirect()->route('roles.index')->with('success', 'Role created successfully!');
    }

    public function edit(Role $role)
    {
        if (in_array($role->name, self::LOCKED, true)) {
            return redirect()->route('roles.index')->with('error', "The {$role->name} role cannot be edited.");
        }

        $permissions = StaffPermissions::assignable();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if (in_array($role->name, self::LOCKED, true)) {
            return redirect()->route('roles.index')->with('error', "The {$role->name} role cannot be edited.");
        }

        $data = $this->validated($request, $role);

        // Permissions hidden from the editor (retail module off) keep their current state.
        $hidden = $role->permissions->pluck('name')
            ->reject(fn ($name) => StaffPermissions::assignable()->pluck('name')->contains($name))
            ->all();

        if (! in_array($role->name, self::SYSTEM, true)) {
            $role->update(['name' => $data['name']]);
        }
        $role->syncPermissions(array_values(array_unique(array_merge($data['permissions'], $hidden))));

        return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        $assignable = StaffPermissions::assignable()->pluck('name')->all();

        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($assignable)],
        ]);
    }
}
