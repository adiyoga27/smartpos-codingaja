<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Role::withCount('permissions');
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                $actions = '<a href="'.route('roles.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a> ';
                $actions .= '<form action="'.route('roles.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus role ini?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>';

                return [
                    $item->name,
                    $item->permissions_count,
                    $actions,
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.roles.index');
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy(function ($p) {
            return explode('_', $p->name, 2)[1] ?? 'other';
        });

        return view('pages.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);
        $role = Role::create(['name' => $validated['name']]);
        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy(function ($p) {
            return explode('_', $p->name, 2)[1] ?? 'other';
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('pages.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
        ]);
        $role->name = $validated['name'];
        $role->save();
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
