<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // Shows the list of all roles and their permissions
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles'));
    }

    // Shows the form to create a new role
    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    // Saves the new role to the database
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Role created successfully!');
    }

    // NEW: Shows the form to edit an existing role
    public function edit(Role $role)
    {
        // Prevent editing the super Admin role to avoid locking yourself out
        if ($role->name === 'Shop Owner') {
            return redirect()->route('roles.index')->with('error', 'The Shop Owner role cannot be edited.');
        }

        $permissions = Permission::all();
        // Get the names of the permissions this role already has
        $rolePermissions = $role->permissions->pluck('name')->toArray(); 

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    // NEW: Saves the updated role to the database
    public function update(Request $request, Role $role)
    {
        if ($role->name === 'Shop Owner') {
            return redirect()->route('roles.index')->with('error', 'The Shop Owner role cannot be edited.');
        }

        $request->validate([
            // Ensure name is unique, but ignore this exact role's current name
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'required|array'
        ]);

        // Update the name
        $role->update(['name' => $request->name]);
        
        // Sync updates the permissions (removes unchecked ones, adds checked ones)
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully!');
    }
}