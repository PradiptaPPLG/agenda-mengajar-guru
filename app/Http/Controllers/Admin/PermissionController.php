<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('category')->orderBy('name')->get();
        $categories = PermissionCategory::orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'label' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:permission_categories,id',
        ]);

        Permission::create($validated);

        return back()->with('success', 'Permission berhasil ditambahkan.');
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->ignore($permission->id),
            ],
            'label' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:permission_categories,id',
        ]);

        $permission->update($validated);

        return back()->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return back()->with('success', 'Permission berhasil dihapus.');
    }
}
