<x-layouts.admin>
    <x-slot:title>Permission</x-slot:title>
    <x-slot:actions>
        <button type="button" onclick="document.getElementById('addModal').showModal()"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Permission
        </button>
    </x-slot:actions>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Deskripsi (Label)</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Slug (Name)</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Kategori</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($permissions as $permission)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $permission->label ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $permission->name }}</td>
                        <td class="px-4 py-3">
                            @if($permission->category)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $permission->category->name }}
                                </span>
                            @else
                                <span class="text-slate-400 italic text-xs">Tanpa Kategori</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <x-action-dropdown 
                                    :deleteUrl="route('admin.permissions.destroy', $permission)" 
                                    deleteMessage="Yakin ingin menghapus permission {{ $permission->name }}?"
                                >
                                    <button type="button" 
                                            onclick="openEditModal({{ $permission->id }}, '{{ addslashes($permission->name) }}', '{{ addslashes($permission->label) }}', '{{ $permission->category_id }}')" 
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
                        <td colspan="4" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada permission.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Modal --}}
    <dialog id="addModal" class="rounded-2xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm m-auto border-0">
        <form action="{{ route('admin.permissions.store') }}" method="POST" class="bg-white rounded-2xl overflow-hidden">
            @csrf
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Tambah Permission</h3>
                <button type="button" onclick="document.getElementById('addModal').close()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi / Label</label>
                    <input type="text" name="label" placeholder="Contoh: Input / Edit Materi Pembelajaran" class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Slug / Nama Teknis (Tanpa Spasi)</label>
                    <input type="text" name="name" placeholder="contoh: agenda.isi_materi" required class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                    <select name="category_id" class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
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
                <h3 class="text-base font-bold text-slate-900">Edit Permission</h3>
                <button type="button" onclick="document.getElementById('editModal').close()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi / Label</label>
                    <input type="text" name="label" id="editLabel" class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Slug / Nama Teknis</label>
                    <input type="text" name="name" id="editName" required class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                    <select name="category_id" id="editCategory" class="block w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50">
                <button type="button" onclick="document.getElementById('editModal').close()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 shadow-sm transition-colors">Simpan</button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditModal(id, name, label, category_id) {
            document.getElementById('editForm').action = `/admin/permissions/${id}`;
            document.getElementById('editName').value = name;
            document.getElementById('editLabel').value = label;
            document.getElementById('editCategory').value = category_id;
            document.getElementById('editModal').showModal();
        }
    </script>
</x-layouts.admin>
