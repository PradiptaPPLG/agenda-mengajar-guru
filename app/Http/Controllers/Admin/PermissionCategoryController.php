<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Permission;

class PermissionCategoryController extends Controller
{
    public function index()
    {
        $categories = PermissionCategory::orderBy('name')->get();

        return view('admin.permission-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permission_categories,name',
        ]);

        PermissionCategory::create($validated);

        return back()->with('success', 'Kategori Permission berhasil ditambahkan.');
    }

    public function update(Request $request, PermissionCategory $permissionCategory)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permission_categories')->ignore($permissionCategory->id),
            ],
        ]);

        $permissionCategory->update($validated);

        return back()->with('success', 'Kategori Permission berhasil diperbarui.');
    }

    public function destroy(PermissionCategory $permissionCategory)
    {
        // Nullify all permissions using this category
        Permission::where('category_id', $permissionCategory->id)->update(['category_id' => null]);
        $permissionCategory->delete();

        return back()->with('success', 'Kategori Permission berhasil dihapus.');
    }
}
