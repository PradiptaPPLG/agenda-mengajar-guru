<x-layouts.guru>
    <x-slot:title>{{ $jadwal->mataPelajaran->nama }}</x-slot:title>

    <div class="px-4 py-4 space-y-4">
        {{-- Back + header --}}
        <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors mb-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>

        {{-- Info card --}}
        <div class="bg-blue-600 rounded-2xl p-4 text-white">
            <p class="text-lg font-bold">{{ $jadwal->mataPelajaran->nama }}</p>
            <p class="text-sm text-blue-100 mt-0.5">{{ $jadwal->kelas->nama }}</p>
            <div class="flex items-center gap-4 mt-3 text-sm text-blue-100">
                <span>{{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} – {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}</span>
                <span>{{ $tanggal->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>

        <form action="{{ route('guru.pertemuan.save-all', $pertemuan->id) }}" method="POST" id="main-form" class="space-y-4">
            @csrf
            @method('PATCH')

            @if($isLocked)
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="text-sm font-bold text-red-800">Waktu Pengisian Ditutup</p>
                    <p class="text-xs text-red-600 mt-1">Batas waktu pengisian atau pengubahan data untuk tanggal ini sudah berakhir (maksimal 7 hari ke belakang). Anda hanya dapat melihat data yang sudah ada.</p>
                </div>
            </div>
            @endif

            {{-- ═══ KEHADIRAN GURU ═══ --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Kehadiran Saya</h2>
                    @if($pertemuan->kehadiranGuru)
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full
                            {{ match($pertemuan->kehadiranGuru->status) {
                                'hadir' => 'badge-hadir',
                                'sakit' => 'badge-sakit',
                                'dispensasi' => 'badge-dispensasi',
                                default => 'badge-alpa',
                            } }}">
                            {{ $pertemuan->kehadiranGuru->status_label }}
                        </span>
                    @endif
                </div>

                @if($pertemuan->kehadiranGuru)
                {{-- Already checked in --}}
                <div class="p-4">
                    <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Absen tercatat pukul {{ $pertemuan->kehadiranGuru->waktu_hadir?->format('H:i') }}</p>
                            @if($pertemuan->kehadiranGuru->jenis_alpa)
                            <p class="text-xs text-slate-500 mt-0.5">{{ $pertemuan->kehadiranGuru->jenis_alpa_label }}
                                @if($pertemuan->kehadiranGuru->guru_pengganti_nama)
                                 — Guru Pengganti: <strong>{{ $pertemuan->kehadiranGuru->guru_pengganti_nama }}</strong>
                                @endif
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                {{-- Check in form --}}
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-2">Status Kehadiran</label>
                        <div class="grid grid-cols-4 gap-2">
                            @php
                                $statusStyles = [
                                    'hadir' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:border-emerald-300',
                                    'sakit' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 hover:border-amber-300',
                                    'alpa' => 'peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:border-red-300',
                                    'dispensasi' => 'peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 hover:border-purple-300',
                                ];
                            @endphp
                            @foreach(['hadir' => 'Hadir', 'sakit' => 'Sakit', 'alpa' => 'Alpa', 'dispensasi' => 'Dispensasi'] as $val => $label)
                            <label class="relative">
                                <input type="radio" name="status" value="{{ $val }}" class="sr-only peer track-change"
                                       {{ old('status') === $val ? 'checked' : '' }} onchange="handleStatusChange(this.value)">
                                <div class="text-center py-2.5 rounded-xl border-2 border-slate-200 cursor-pointer transition-all
                                            {{ $statusStyles[$val] }}">
                                    <span class="text-sm font-semibold">{{ $label }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('status')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Alpa options (shown only when alpa selected) --}}
                    <div id="alpa-section" class="{{ old('status') === 'alpa' ? '' : 'hidden' }} space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-2">Jenis Ketidakhadiran</label>
                            <div class="space-y-2">
                                @foreach(['ada_tugas' => 'Ada Tugas (ada penugasan)', 'tanpa_tugas' => 'Tanpa Tugas', 'guru_pengganti' => 'Ada Guru Pengganti'] as $val => $label)
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="jenis_alpa" value="{{ $val }}" class="text-blue-600 track-change"
                                           {{ old('jenis_alpa') === $val ? 'checked' : '' }} onchange="handleJenisAlpa(this.value)">
                                    <span class="text-sm text-slate-700">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('jenis_alpa')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div id="guru-pengganti-section" class="{{ old('jenis_alpa') === 'guru_pengganti' ? '' : 'hidden' }}">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Guru Pengganti</label>
                            <input type="text" name="guru_pengganti_nama" value="{{ old('guru_pengganti_nama') }}"
                                   placeholder="Nama guru pengganti..."
                                   class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 track-change">
                            @error('guru_pengganti_nama')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Keterangan (opsional)</label>
                        <textarea name="keterangan" rows="2" placeholder="Tambahan keterangan..."
                                  class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none track-change">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                @endif
            </div>

            {{-- ═══ MATERI & PENUGASAN ═══ --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Materi & Penugasan</h2>
                    <span id="draft-status" class="text-[11px] text-emerald-600 font-medium hidden"></span>
                </div>
                <div id="draft-alert" class="hidden m-4 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Ditemukan draf ketikan yang belum tersimpan (<span id="draft-time"></span>).</span>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" onclick="restoreDraft()" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg transition-colors">
                            Pulihkan
                        </button>
                        <button type="button" onclick="discardDraft()" class="px-2 py-1 text-slate-500 hover:text-slate-800 transition-colors">
                            Abaikan
                        </button>
                    </div>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Materi Ajar</label>
                        <textarea id="input-materi-ajar" name="materi_ajar" rows="3" placeholder="Tuliskan materi yang diajarkan..."
                                  class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none track-change">{{ $pertemuan->materi_ajar }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Penugasan</label>
                        <textarea id="input-penugasan" name="penugasan" rows="3" placeholder="Tuliskan penugasan untuk siswa (jika ada)..."
                                  class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none track-change">{{ $pertemuan->penugasan }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ═══ DAFTAR SISWA ═══ --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Daftar Kehadiran Siswa</h2>
                    <span class="text-xs text-slate-500">{{ $pertemuan->kehadiranSiswas->count() }} siswa</span>
                </div>

                @if($pertemuan->kehadiranSiswas->count() > 0)
                <div class="divide-y divide-slate-100 pb-2">
                    @foreach($pertemuan->kehadiranSiswas->sortBy('siswa.name') as $ks)
                    <div class="px-4 py-3 flex items-center gap-3">
                        {{-- Avatar --}}
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                            <span class="text-xs font-bold text-slate-600">{{ substr($ks->siswa->name ?? '?', 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $ks->siswa->name }}</p>
                        </div>
                        {{-- Status dropdown --}}
                        <select name="siswa[{{ $ks->siswa_id }}][status]"
                                class="track-change text-xs font-medium border rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500
                                       {{ match($ks->status) {
                                           'hadir' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                           'sakit' => 'border-amber-200 bg-amber-50 text-amber-800',
                                           'izin' => 'border-sky-200 bg-sky-50 text-sky-800',
                                           'alpa' => 'border-red-200 bg-red-50 text-red-800',
                                           'dispensasi' => 'border-purple-200 bg-purple-50 text-purple-800',
                                           default => 'border-slate-200 bg-slate-50',
                                       } }}">
                            <option value="hadir" {{ $ks->status === 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="sakit" {{ $ks->status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="izin" {{ $ks->status === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="alpa" {{ $ks->status === 'alpa' ? 'selected' : '' }}>Alpa</option>
                            <option value="dispensasi" {{ $ks->status === 'dispensasi' ? 'selected' : '' }}>Dispensasi</option>
                        </select>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-6 text-center text-sm text-slate-400">
                    Tidak ada siswa di kelas ini
                </div>
                @endif
            </div>

            {{-- Floating / sticky save button --}}
            @if(!$isLocked)
            <div class="sticky bottom-4 z-10 mt-6">
                <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.12)] flex items-center justify-between">
                    <span class="text-xs text-slate-500 px-2">Pastikan semua data sudah benar</span>
                    <button type="submit"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                        Simpan Semua
                    </button>
                </div>
            </div>
            @endif
        </form>

        {{-- Foto Bukti dari Siswa --}}
        @if($pertemuan->fotoBuktis->count() > 0)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mt-6">
            <div class="px-4 py-3 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">Foto Bukti Siswa ({{ $pertemuan->fotoBuktis->count() }})</h2>
            </div>
            <div class="p-4 grid grid-cols-3 gap-2">
                @foreach($pertemuan->fotoBuktis as $foto)
                <div class="relative rounded-xl overflow-hidden aspect-square bg-slate-100">
                    <img src="{{ Storage::url($foto->foto_path) }}" alt="Bukti {{ $foto->siswa->name }}"
                         class="w-full h-full object-cover">
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-1.5">
                        <p class="text-white text-xs truncate">{{ $foto->siswa->name }}</p>
                        <span class="text-xs {{ $foto->status_guru_dilaporkan === 'hadir' ? 'text-emerald-300' : 'text-red-300' }}">
                            {{ ucfirst($foto->status_guru_dilaporkan) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function handleStatusChange(val) {
            document.getElementById('alpa-section').classList.toggle('hidden', val !== 'alpa');
        }
        function handleJenisAlpa(val) {
            document.getElementById('guru-pengganti-section').classList.toggle('hidden', val !== 'guru_pengganti');
        }
        
        // Update select background color on change
        document.querySelectorAll('select[name^="siswa"]').forEach(sel => {
            sel.addEventListener('change', function() {
                const colors = {
                    hadir: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    sakit: 'border-amber-200 bg-amber-50 text-amber-800',
                    izin: 'border-sky-200 bg-sky-50 text-sky-800',
                    alpa: 'border-red-200 bg-red-50 text-red-800',
                    dispensasi: 'border-purple-200 bg-purple-50 text-purple-800',
                };
                this.className = `track-change text-xs font-medium border rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 ${colors[this.value] || ''}`;
            });
        });

        // Dirty checking / beforeunload warning
        let isDirty = false;
        
        // Track changes on any input with class 'track-change'
        document.querySelectorAll('.track-change').forEach(input => {
            input.addEventListener('change', () => {
                isDirty = true;
            });
            input.addEventListener('input', () => {
                isDirty = true;
            });
        });

        // Auto-save draft in localStorage for materi & penugasan
        const DRAFT_KEY = 'agenda_draft_{{ $pertemuan->id }}';
        const materiInput = document.getElementById('input-materi-ajar');
        const penugasanInput = document.getElementById('input-penugasan');
        const draftAlert = document.getElementById('draft-alert');
        const draftTime = document.getElementById('draft-time');
        const draftStatus = document.getElementById('draft-status');

        let saveTimeout = null;

        function saveDraft() {
            if (!materiInput || !penugasanInput) return;
            const draft = {
                materi_ajar: materiInput.value,
                penugasan: penugasanInput.value,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                timestamp: Date.now()
            };
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            if (draftStatus) {
                draftStatus.textContent = 'Draf tersimpan lokal ✓';
                draftStatus.classList.remove('hidden');
                setTimeout(() => {
                    draftStatus.classList.add('hidden');
                }, 3000);
            }
        }

        function checkForSavedDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;
                const draft = JSON.parse(raw);
                if (!draft) return;

                const currentMateri = materiInput ? materiInput.value.trim() : '';
                const currentPenugasan = penugasanInput ? penugasanInput.value.trim() : '';
                const draftMateri = (draft.materi_ajar || '').trim();
                const draftPenugasan = (draft.penugasan || '').trim();

                const isDifferent = (draftMateri !== currentMateri) || (draftPenugasan !== currentPenugasan);
                const hasContent = draftMateri.length > 0 || draftPenugasan.length > 0;

                if (isDifferent && hasContent) {
                    if (draftTime) draftTime.textContent = draft.time || 'baru saja';
                    if (draftAlert) draftAlert.classList.remove('hidden');
                }
            } catch (e) {
                console.error('Error reading draft:', e);
            }
        }

        function restoreDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;
                const draft = JSON.parse(raw);
                if (materiInput && draft.materi_ajar !== undefined) materiInput.value = draft.materi_ajar;
                if (penugasanInput && draft.penugasan !== undefined) penugasanInput.value = draft.penugasan;
                if (draftAlert) draftAlert.classList.add('hidden');
                isDirty = true;
            } catch (e) {
                console.error('Error restoring draft:', e);
            }
        }

        function discardDraft() {
            localStorage.removeItem(DRAFT_KEY);
            if (draftAlert) draftAlert.classList.add('hidden');
        }

        if (materiInput && penugasanInput) {
            [materiInput, penugasanInput].forEach(el => {
                el.addEventListener('input', () => {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(saveDraft, 800);
                });
            });
            checkForSavedDraft();
        }

        // Clear dirty flag and local draft when the form is submitted
        document.getElementById('main-form').addEventListener('submit', () => {
            isDirty = false;
            localStorage.removeItem(DRAFT_KEY);
        });

        // Display confirmation dialog before leaving
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = ''; // Standard way to show the browser's "Leave site?" prompt
            }
        });
    </script>
    @endpush
</x-layouts.guru>
