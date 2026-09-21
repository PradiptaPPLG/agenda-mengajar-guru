<x-layouts.admin title="Edit Role">
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-slate-900">Edit Role</h1>
            <p class="mt-1 text-sm text-slate-600">Perbarui role dan atur permission yang dimiliki.</p>
        </div>

        <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl">
            @csrf @method('PUT')
            
            <div class="px-4 py-6 sm:p-8">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="name" class="block text-sm font-medium leading-6 text-slate-900">Nama Role</label>
                        <div class="mt-2">
                            <input type="text" name="name" id="name" value="{{ $role->name }}" required class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div class="sm:col-span-6 mt-2">
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                            <div class="bg-slate-50 px-5 py-4 flex items-center justify-between border-b border-slate-200">
                                <h3 class="font-semibold text-slate-800">Assign Permissions</h3>
                                <span class="bg-orange-500 text-white text-xs px-2.5 py-1 rounded-full font-medium"><span id="selected-count">0</span> dipilih</span>
                            </div>
                            
                            <div class="p-5 bg-slate-50/50">
                                <div class="relative mb-6">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="searchPermission" placeholder="Cari permission (deskripsi/slug)..." class="block w-full rounded-lg border-0 py-2 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-orange-500 sm:text-sm sm:leading-6 bg-white">
                                </div>

                                <div class="space-y-3" id="categoriesContainer">
                                    @foreach($categories as $category)
                                        <div class="border border-slate-200 rounded-lg overflow-hidden bg-white category-card">
                                            <button type="button" class="w-full bg-white px-4 py-3 flex items-center text-left text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors border-b border-slate-100">
                                                <svg class="w-5 h-5 text-orange-400 mr-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                                {{ $category->name }}
                                            </button>
                                            <div class="p-4 bg-slate-50/30 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6">
                                                @forelse($category->permissions as $permission)
                                                    <div class="flex items-start permission-item" data-search="{{ strtolower(($permission->label ?? $permission->name) . ' ' . $permission->name) }}">
                                                        <div class="flex h-6 items-center">
                                                            <input id="perm_{{ $permission->id }}" name="permissions[]" value="{{ $permission->name }}" type="checkbox" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }} class="perm-checkbox h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                                        </div>
                                                        <div class="ml-3 text-sm leading-5">
                                                            <label for="perm_{{ $permission->id }}" class="font-medium text-slate-700 cursor-pointer block">{{ $permission->label ?? $permission->name }}</label>
                                                            <span class="text-xs text-slate-400">{{ $permission->name }}</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-slate-400 italic col-span-full">Belum ada permission pada kategori ini.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($uncategorized_permissions->count() > 0)
                                        <div class="border border-slate-200 rounded-lg overflow-hidden bg-white category-card">
                                            <button type="button" class="w-full bg-white px-4 py-3 flex items-center text-left text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors border-b border-slate-100">
                                                <svg class="w-5 h-5 text-orange-400 mr-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                                Lainnya (Tanpa Kategori)
                                            </button>
                                            <div class="p-4 bg-slate-50/30 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6">
                                                @foreach($uncategorized_permissions as $permission)
                                                    <div class="flex items-start permission-item" data-search="{{ strtolower(($permission->label ?? $permission->name) . ' ' . $permission->name) }}">
                                                        <div class="flex h-6 items-center">
                                                            <input id="perm_{{ $permission->id }}" name="permissions[]" value="{{ $permission->name }}" type="checkbox" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }} class="perm-checkbox h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                                        </div>
                                                        <div class="ml-3 text-sm leading-5">
                                                            <label for="perm_{{ $permission->id }}" class="font-medium text-slate-700 cursor-pointer block">{{ $permission->label ?? $permission->name }}</label>
                                                            <span class="text-xs text-slate-400">{{ $permission->name }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-x-6 border-t border-slate-200 px-4 py-4 sm:px-8 bg-slate-50 rounded-b-xl">
                <a href="{{ route('admin.roles.index') }}" class="text-sm font-semibold leading-6 text-slate-900">Batal</a>
                <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-layouts.admin>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchPermission');
    const permissionItems = document.querySelectorAll('.permission-item');
    const checkboxes = document.querySelectorAll('.perm-checkbox');
    const selectedCount = document.getElementById('selected-count');

    function updateCount() {
        const count = Array.from(checkboxes).filter(cb => cb.checked).length;
        selectedCount.textContent = count;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
    updateCount();

    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        
        permissionItems.forEach(item => {
            const searchData = item.getAttribute('data-search');
            if (searchData.includes(term)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>
@endpush
