<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Agenda Mengajar' }} — {{ \App\Models\Setting::get('school_name', 'Sekolah') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Alpine Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/anchor@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased" x-data="{ showGlobalLogoutModal: false, logoutForm: null }" @open-logout-modal.window="showGlobalLogoutModal = true; logoutForm = $event.detail.form">
    {{ $slot }}

    <!-- Global Logout Modal -->
    <div x-show="showGlobalLogoutModal" style="display: none;" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="showGlobalLogoutModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="showGlobalLogoutModal" 
                     @click.away="showGlobalLogoutModal = false"
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4" 
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
                     x-transition:leave-end="opacity-0 scale-90 translate-y-4" 
                     class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl shadow-rose-950/20 border border-slate-100 transition-all sm:my-8 sm:w-full sm:max-w-md">

                    <div class="p-6 sm:p-7">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-red-600 ring-8 ring-rose-500/10 shadow-inner">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold tracking-tight text-slate-900" id="modal-title">Konfirmasi Keluar</h3>
                                <p class="mt-1 text-sm text-slate-500 leading-relaxed">Apakah Anda yakin ingin keluar dari akun ini? Anda harus masuk kembali untuk mengakses sistem.</p>
                            </div>
                        </div>

                        <div class="mt-7 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                            <button type="button" @click="showGlobalLogoutModal = false" class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 active:scale-98 transition-all cursor-pointer">
                                Batal
                            </button>
                            <button type="button" @click="if(logoutForm) logoutForm.submit()" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/25 hover:from-red-500 hover:to-rose-500 active:scale-98 transition-all cursor-pointer">
                                <span>Ya, Keluar</span>
                                <svg class="w-4 h-4 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Delete Modal -->
    <div x-data="{ 
            show: false, 
            form: null, 
            countdown: 5, 
            timer: null,
            openModal(e) {
                this.form = e.detail.form;
                this.show = true;
                this.countdown = 5;
                if(this.timer) clearInterval(this.timer);
                this.timer = setInterval(() => {
                    this.countdown--;
                    if(this.countdown <= 0) clearInterval(this.timer);
                }, 1000);
            },
            confirmDelete() {
                if(this.countdown <= 0 && this.form) {
                    this.form.submit();
                }
            }
         }" 
         @open-delete-modal.window="openModal($event)"
         x-show="show" style="display: none;" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity"></div>
             
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="show"
                     @click.away="show = false"
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4" 
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
                     x-transition:leave-end="opacity-0 scale-90 translate-y-4"
                     class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl shadow-red-950/20 border border-slate-100 transition-all sm:my-8 sm:w-full sm:max-w-md">

                    <div class="p-6 sm:p-7">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-600 ring-8 ring-red-500/10 shadow-inner">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold tracking-tight text-slate-900" id="modal-title">Konfirmasi Penghapusan</h3>
                                <p class="mt-1 text-sm text-slate-500 leading-relaxed">Apakah kamu yakin ingin menghapus data ini? Tindakan ini <strong class="text-red-600 font-semibold">permanen</strong> dan tidak dapat dibatalkan.</p>
                            </div>
                        </div>

                        <div class="mt-7 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                            <button type="button" @click="show = false" class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 active:scale-98 transition-all cursor-pointer">
                                Batal
                            </button>
                            <button type="button" @click="confirmDelete" :disabled="countdown > 0" :class="countdown > 0 ? 'opacity-60 cursor-not-allowed bg-red-400' : 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 shadow-lg shadow-red-500/25 active:scale-98'" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all cursor-pointer">
                                <span x-show="countdown > 0" x-text="'Menunggu (' + countdown + 's)'"></span>
                                <span x-show="countdown <= 0">Ya, Hapus Data</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.filterSubmitTimer = null;
        function debouncedFilterSubmit(formElement, delay = 400) {
            if (window.filterSubmitTimer) clearTimeout(window.filterSubmitTimer);
            window.filterSubmitTimer = setTimeout(() => {
                if (formElement) {
                    formElement.submit();
                }
            }, delay);
        }
    </script>
    @stack('scripts')
</body>
</html>
