<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::withCount('permissions');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $query->orderBy('name', 'asc');

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if ($validated['permissions'] ?? null) {
            $role->syncPermissions($validated['permissions']);
        }

        return $this->created($role->load('permissions'), 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): JsonResponse
    {
        return $this->success($role->load('permissions'));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return $this->success(null, 'Role berhasil dihapus.');
    }

    public function permissions(): JsonResponse
    {
        return $this->success(Permission::orderBy('name')->get());
    }
}
