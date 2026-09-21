<x-layouts.admin>
    <x-slot:title>Manajemen Role</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('admin.roles.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Role
        </a>
    </x-slot:actions>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama Role</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Total Permission</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($roles as $role)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-800">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                {{ $role->name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $role->permissions->count() }} permission
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <x-action-dropdown 
                                    :editUrl="route('admin.roles.edit', $role)" 
                                    :deleteUrl="route('admin.roles.destroy', $role)" 
                                    deleteMessage="Yakin ingin menghapus role {{ $role->name }}?"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada role yang dibuat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
