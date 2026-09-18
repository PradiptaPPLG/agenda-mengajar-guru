@props(['editUrl' => null, 'deleteUrl' => null, 'deleteMessage' => 'Hapus data ini?'])

<div x-data="{ open: false }" class="relative inline-block text-left" @click.away="open = false">
    <button x-ref="button" @click="open = !open" type="button" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" aria-haspopup="true" :aria-expanded="open">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
    </button>

    <template x-teleport="body">
        <div x-show="open" 
             x-anchor.bottom-end.offset.4="$refs.button"
             x-transition:enter="transition ease-out duration-100" 
             x-transition:enter-start="transform opacity-0 scale-95" 
             x-transition:enter-end="transform opacity-100 scale-100" 
             x-transition:leave="transition ease-in duration-75" 
             x-transition:leave-start="transform opacity-100 scale-100" 
             x-transition:leave-end="transform opacity-0 scale-95" 
             class="absolute z-[100] w-36 rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" 
             role="menu" aria-orientation="vertical" tabindex="-1" style="display: none;">
        <div class="py-1" role="none">
            @if($editUrl)
            <a href="{{ $editUrl }}" class="group flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600" role="menuitem">
                <svg class="mr-3 h-4 w-4 text-slate-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            @endif

            @if($deleteUrl)
            <form action="{{ $deleteUrl }}" method="POST" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('open-delete-modal', { detail: { form: this } }));">
                @csrf @method('DELETE')
                <button type="submit" class="group flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-red-600" role="menuitem">
                    <svg class="mr-3 h-4 w-4 text-slate-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
            </form>
            @endif
            
            {{ $slot }}
        </div>
    </template>
</div>
