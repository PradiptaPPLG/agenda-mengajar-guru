<x-layouts.admin>
    <x-slot:title>Pemetaan Blok Terpusat</x-slot:title>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pemetaan Blok Terpusat</h1>
            <p class="text-slate-500 mt-1">Pilih kelas untuk dimasukkan ke kelompok blok atau dibagi menjadi 2 kelompok paralel.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <p class="font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <form action="{{ route('admin.pemetaan-blok.update') }}" method="POST" id="formPemetaanBlok">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Kolom Kelompok A -->
            <div class="bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden flex flex-col h-full">
                <div class="bg-blue-50 border-b border-blue-100 px-5 py-4">
                    <h3 class="font-bold text-blue-800 text-lg flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        Kelompok A (Umum)
                    </h3>
                    <p class="text-blue-600 text-xs mt-1">Ceklis kelas yang masuk ke blok A.</p>
                </div>
                <div class="p-4 overflow-y-auto max-h-[400px]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($kelas as $k)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors chk-wrapper-a-{{$k->id}}">
                            <input type="checkbox" name="kelompok_a[]" value="{{$k->id}}" class="chk-kelas chk-a w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" data-id="{{$k->id}}" {{ $k->blok_awal == 'kelompok_a' && $k->is_sistem_blok ? 'checked' : '' }}>
                            <div>
                                <div class="font-medium text-slate-900 text-sm">{{ $k->nama }}</div>
                                <div class="text-[11px] text-slate-500">{{ $k->tingkat }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Kolom Kelompok B -->
            <div class="bg-white rounded-xl shadow-sm border border-amber-200 overflow-hidden flex flex-col h-full">
                <div class="bg-amber-50 border-b border-amber-100 px-5 py-4">
                    <h3 class="font-bold text-amber-800 text-lg flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
                        Kelompok B (Kejuruan)
                    </h3>
                    <p class="text-amber-700 text-xs mt-1">Ceklis kelas yang masuk ke blok B.</p>
                </div>
                <div class="p-4 overflow-y-auto max-h-[400px]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($kelas as $k)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors chk-wrapper-b-{{$k->id}}">
                            <input type="checkbox" name="kelompok_b[]" value="{{$k->id}}" class="chk-kelas chk-b w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500" data-id="{{$k->id}}" {{ $k->blok_awal == 'kelompok_b' && $k->is_sistem_blok ? 'checked' : '' }}>
                            <div>
                                <div class="font-medium text-slate-900 text-sm">{{ $k->nama }}</div>
                                <div class="text-[11px] text-slate-500">{{ $k->tingkat }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kelas Split -->
        <div class="bg-white rounded-xl shadow-sm border border-purple-200 overflow-hidden mt-6">
            <div class="bg-purple-50 border-b border-purple-100 px-5 py-4">
                <h3 class="font-bold text-purple-800 text-lg flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                    Kondisi Khusus: 1 Kelas Dibagi 2 Kelompok (Split)
                </h3>
                <p class="text-purple-700 text-xs mt-1">Ceklis kelas yang siswanya dibagi menjadi kelompok A dan B, lalu atur siswanya di panel yang muncul di bawahnya.</p>
            </div>
            
            <div class="p-5 border-b border-purple-100">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($kelas as $k)
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 bg-slate-50 hover:bg-white cursor-pointer transition-colors chk-wrapper-split-{{$k->id}}">
                        <input type="checkbox" name="split[]" value="{{$k->id}}" class="chk-kelas chk-split w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500" data-id="{{$k->id}}" {{ ($k->model_rotasi === 'split_harian' || $k->blok_awal === 'split') && $k->is_sistem_blok ? 'checked' : '' }}>
                        <div class="font-medium text-slate-900 text-sm">{{ $k->nama }}</div>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Area untuk membagi siswa -->
            <div id="split-students-area" class="p-5 flex flex-col gap-6 bg-slate-50/50">
                @foreach($kelas as $k)
                <div id="split-panel-{{$k->id}}" class="split-panel hidden bg-white border border-purple-200 shadow-sm rounded-xl overflow-hidden">
                    <div class="bg-purple-50 px-4 py-3 border-b border-purple-100 flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-purple-900">Bagi Siswa: {{ $k->nama }}</h4>
                            <p class="text-[11px] text-purple-600">Pilih kelompok untuk setiap siswa.</p>
                        </div>
                        <div class="text-xs font-semibold text-purple-800 bg-purple-100 px-3 py-1 rounded-full">
                            Total: {{ $k->siswaProfiles->count() }} Siswa
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @forelse($k->siswaProfiles as $index => $s)
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 flex flex-col justify-between">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-5 h-5 rounded bg-slate-200 text-slate-500 flex items-center justify-center text-[10px] font-bold shrink-0">
                                        {{ $index + 1 }}
                                    </div>
                                    <span class="text-xs font-medium text-slate-800 truncate" title="{{ $s->user->name }}">
                                        {{ $s->user->name }}
                                    </span>
                                </div>
                                <div class="flex border border-slate-300 rounded-md overflow-hidden bg-white">
                                    <label class="flex-1 cursor-pointer text-center relative">
                                        <input type="radio" name="siswa[{{$s->id}}]" value="kelompok_a" class="peer sr-only" {{ $s->kelompok_blok == 'kelompok_a' ? 'checked' : '' }} required>
                                        <div class="py-1.5 text-xs font-semibold text-slate-500 peer-checked:bg-blue-500 peer-checked:text-white transition-colors">Kel. A</div>
                                    </label>
                                    <div class="w-px bg-slate-200"></div>
                                    <label class="flex-1 cursor-pointer text-center relative">
                                        <input type="radio" name="siswa[{{$s->id}}]" value="kelompok_b" class="peer sr-only" {{ $s->kelompok_blok == 'kelompok_b' ? 'checked' : '' }}>
                                        <div class="py-1.5 text-xs font-semibold text-slate-500 peer-checked:bg-amber-500 peer-checked:text-white transition-colors">Kel. B</div>
                                    </label>
                                </div>
                            </div>
                            @empty
                            <div class="col-span-full py-6 text-center text-slate-500 text-sm bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                Belum ada data siswa di kelas ini.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endforeach
                
                <div id="no-split-selected" class="py-8 text-center text-slate-400 text-sm font-medium">
                    Belum ada kelas split yang dipilih.
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold shadow-sm hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Semua Pemetaan
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function updateState() {
                const checkedClasses = {
                    a: Array.from(document.querySelectorAll('.chk-a:checked')).map(c => c.dataset.id),
                    b: Array.from(document.querySelectorAll('.chk-b:checked')).map(c => c.dataset.id),
                    split: Array.from(document.querySelectorAll('.chk-split:checked')).map(c => c.dataset.id),
                };

                let hasSplit = false;

                // Loop over all checkboxes and manage disabled state
                document.querySelectorAll('.chk-kelas').forEach(chk => {
                    const id = chk.dataset.id;
                    const wrapper = document.querySelector(`.chk-wrapper-${chk.classList.contains('chk-a') ? 'a' : (chk.classList.contains('chk-b') ? 'b' : 'split')}-${id}`);
                    
                    const isCheckedInA = checkedClasses.a.includes(id);
                    const isCheckedInB = checkedClasses.b.includes(id);
                    const isCheckedInSplit = checkedClasses.split.includes(id);
                    
                    if (chk.classList.contains('chk-a')) {
                        if (!chk.checked && (isCheckedInB || isCheckedInSplit)) {
                            chk.disabled = true;
                            if (wrapper) wrapper.classList.add('opacity-40', 'bg-slate-100');
                        } else {
                            chk.disabled = false;
                            if (wrapper) wrapper.classList.remove('opacity-40', 'bg-slate-100');
                        }
                    } 
                    else if (chk.classList.contains('chk-b')) {
                        if (!chk.checked && (isCheckedInA || isCheckedInSplit)) {
                            chk.disabled = true;
                            if (wrapper) wrapper.classList.add('opacity-40', 'bg-slate-100');
                        } else {
                            chk.disabled = false;
                            if (wrapper) wrapper.classList.remove('opacity-40', 'bg-slate-100');
                        }
                    } 
                    else if (chk.classList.contains('chk-split')) {
                        if (!chk.checked && (isCheckedInA || isCheckedInB)) {
                            chk.disabled = true;
                            if (wrapper) wrapper.classList.add('opacity-40', 'bg-slate-200');
                        } else {
                            chk.disabled = false;
                            if (wrapper) wrapper.classList.remove('opacity-40', 'bg-slate-200');
                        }
                        
                        // Toggle split panel visibility
                        const panel = document.getElementById('split-panel-' + id);
                        if (panel) {
                            if (chk.checked) {
                                panel.classList.remove('hidden');
                                hasSplit = true;
                            } else {
                                panel.classList.add('hidden');
                                
                                // Disable radio buttons when hidden so they don't submit if not split
                                panel.querySelectorAll('input[type="radio"]').forEach(radio => radio.disabled = true);
                            }
                        }
                        
                        // Re-enable radio buttons if checked
                        if (chk.checked && panel) {
                            panel.querySelectorAll('input[type="radio"]').forEach(radio => radio.disabled = false);
                        }
                    }
                });

                const noSplitMsg = document.getElementById('no-split-selected');
                if (noSplitMsg) {
                    if (hasSplit) {
                        noSplitMsg.classList.add('hidden');
                    } else {
                        noSplitMsg.classList.remove('hidden');
                    }
                }
            }

            document.querySelectorAll('.chk-kelas').forEach(chk => {
                chk.addEventListener('change', updateState);
            });

            // Run once on load
            updateState();
            
            // Prevent form submit if there are split classes but not all students have a group
            document.getElementById('formPemetaanBlok').addEventListener('submit', function(e) {
                // Remove required attribute checking natively to handle it manually or just let native HTML5 handle it since radio inputs have required
                // Wait, HTML5 validation works for visible inputs. Hidden inputs shouldn't be validated.
                // Our logic disables hidden radio inputs, so HTML5 validation should work perfectly.
            });
        });
    </script>
    @endpush
</x-layouts.admin>
