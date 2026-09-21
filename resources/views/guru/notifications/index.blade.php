<x-layouts.guru>
    <x-slot:title>Notifikasi</x-slot:title>

    <div class="px-4 py-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-900">Notifikasi</h2>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <form action="{{ route('guru.notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Tandai semua dibaca</button>
            </form>
            @endif
        </div>

        <div class="space-y-3">
            @forelse($notifications as $notif)
            <div class="bg-white rounded-xl border {{ $notif->read_at ? 'border-slate-200' : 'border-blue-300 bg-blue-50/30' }} p-4 flex gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-800 font-medium leading-snug">{{ $notif->data['message'] ?? 'Ada notifikasi baru' }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[11px] text-slate-500">{{ $notif->created_at->diffForHumans() }}</span>
                        @if(!$notif->read_at)
                        <form action="{{ route('guru.notifications.read', $notif->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-[11px] font-semibold text-blue-600 border border-blue-200 px-2 py-0.5 rounded-full hover:bg-blue-50">Tandai Dibaca</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-600">Belum ada notifikasi.</p>
            </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </div>
</x-layouts.guru>
