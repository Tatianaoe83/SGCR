<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::paginate(15);
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permiso creado exitosamente.');
    }

    public function show(Permission $permission)
    {
        $permission->load('roles');
        return view('permissions.show', compact('permission'));
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'guard_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado exitosamente.');
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return redirect()->route('permissions.index')->with('error', 'No se puede eliminar el permiso porque está asignado a roles.');
        }

        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado exitosamente.');
    }
}
