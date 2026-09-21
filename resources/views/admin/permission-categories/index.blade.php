<x-layouts.admin>
    <x-slot:title>Kategori Permission</x-slot:title>
    <x-slot:actions>
        <button type="button" onclick="document.getElementById('addModal').showModal()"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kategori
        </button>
    </x-slot:actions>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama Kategori</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Total Permission</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-800">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                {{ $category->name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                {{ \Spatie\Permission\Models\Permission::where('category_id', $category->id)->count() }} item
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <x-action-dropdown 
                                    :deleteUrl="route('admin.permission-categories.destroy', $category)" 
                                    deleteMessage="Yakin ingin menghapus kategori {{ $category->name }}? (Permission di dalamnya tidak akan terhapus)"
                                >
                                    <button type="button" 
                                            onclick="openEditModal({{ $category->id }}, '{{ addslashes($category->name) }}')" 
                                            class="group flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600" 
                                            role="menuitem">
                                        <svg class="mr-3 h-4 w-4 text-slate-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                </x-action-dropdown>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada kategori permission.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Modal --}}
    <dialog id="addModal" class="rounded-2xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm m-auto border-0">
        <form action="{{ route('admin.permission-categories.store') }}" method="POST" class="bg-white rounded-2xl overflow-hidden">
            @csrf
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Tambah Kategori</h3>
                <button type="button" onclick="document.getElementById('addModal').close()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Agenda & Jurnal Mengajar" class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50">
                <button type="button" onclick="document.getElementById('addModal').close()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm transition-colors">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- Edit Modal --}}
    <dialog id="editModal" class="rounded-2xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm m-auto border-0">
        <form id="editForm" method="POST" class="bg-white rounded-2xl overflow-hidden">
            @csrf @method('PUT')
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Edit Kategori</h3>
                <button type="button" onclick="document.getElementById('editModal').close()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Kategori</label>
                    <input type="text" name="name" id="editName" required class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50">
                <button type="button" onclick="document.getElementById('editModal').close()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm transition-colors">Simpan</button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditModal(id, name) {
            document.getElementById('editForm').action = `/admin/permission-categories/${id}`;
            document.getElementById('editName').value = name;
            document.getElementById('editModal').showModal();
        }
    </script>
</x-layouts.admin>
