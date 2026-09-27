<div x-data="pwaInstaller()" x-show="showBanner && !isPWA" style="display: none;" class="relative z-[90]">
    {{-- Floating PWA Install Bar --}}
    <div x-show="showBanner && !isPWA" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         class="fixed bottom-4 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 bg-slate-900/95 backdrop-blur-md text-white p-4 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center justify-between gap-4">
        
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-xl bg-white p-1.5 shrink-0 flex items-center justify-center shadow-md">
                <img src="{{ asset('images/logo_new.png') }}" alt="Logo App" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <p class="text-sm font-bold text-white truncate">Install Aplikasi SOPAN</p>
                    <span class="px-1.5 py-0.5 rounded bg-blue-500/30 text-blue-300 text-[10px] font-bold">PWA</span>
                </div>
                <p class="text-xs text-slate-300 truncate">Akses cepat & presensi tanpa browser</p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button @click="installApp()" class="px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-md transition-all active:scale-95 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download App</span>
            </button>
            
            <button @click="dismissBanner()" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition-colors" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- iOS / Safari Instructions Modal --}}
    <div x-show="showIosModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md">
        <div @click.away="showIosModal = false" class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl text-slate-900 border border-slate-100">
            <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center justify-center mb-4 mx-auto p-2">
                <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h3 class="text-base font-bold text-center text-slate-900">Install di HP / Tablet</h3>
            <p class="text-xs text-slate-500 text-center mt-1 leading-relaxed">Ikuti langkah di bawah ini untuk menambahkan aplikasi ke Layar Utama perangkat Anda:</p>
            
            <div class="mt-4 space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs">
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center shrink-0 text-[10px]">1</span>
                    <p class="text-slate-700">Ketuk menu browser / tombol <strong class="text-slate-900">Bagikan (Share)</strong> atau titik tiga di kanan atas.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center shrink-0 text-[10px]">2</span>
                    <p class="text-slate-700">Pilih opsi <strong class="text-slate-900">'Tambahkan ke Layar Utama' (Add to Home Screen)</strong> atau <strong class="text-slate-900">'Install Aplikasi'</strong>.</p>
                </div>
            </div>

            <button @click="showIosModal = false" class="w-full mt-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl transition-colors">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>

<script>
    function pwaInstaller() {
        return {
            isPWA: false,
            showBanner: false,
            showIosModal: false,
            deferredPrompt: null,

            init() {
                const isStandalone = window.matchMedia('(display-mode: standalone)').matches 
                    || window.navigator.standalone === true 
                    || document.referrer.includes('android-app://');
                
                if (isStandalone) {
                    this.isPWA = true;
                    this.showBanner = false;
                    return;
                }

                this.isPWA = false;

                if (sessionStorage.getItem('pwa_banner_dismissed') === '1') {
                    this.showBanner = false;
                } else {
                    this.showBanner = true;
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                    if (sessionStorage.getItem('pwa_banner_dismissed') !== '1') {
                        this.showBanner = true;
                    }
                });

                window.addEventListener('appinstalled', () => {
                    this.isPWA = true;
                    this.showBanner = false;
                    this.deferredPrompt = null;
                });
            },

            installApp() {
                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    this.deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            this.showBanner = false;
                        }
                        this.deferredPrompt = null;
                    });
                } else {
                    this.showIosModal = true;
                }
            },

            dismissBanner() {
                this.showBanner = false;
                sessionStorage.setItem('pwa_banner_dismissed', '1');
            }
        };
    }
</script>
