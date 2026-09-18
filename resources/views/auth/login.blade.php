<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Agenda Mengajar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-slate-100 to-blue-50 flex items-center justify-center p-4">

    <div class="w-full max-w-sm">
        {{-- Login Card --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
            {{-- Logo / App Name --}}
            <div class="text-center mb-8">
                <div class="mb-4 flex justify-center">
                    <img src="{{ asset('images/logo_new.png') }}" alt="Logo" class="w-24 h-24 object-contain">
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Agenda Mengajar</h1>
                <p class="mt-1 text-sm text-slate-500">{{ \App\Models\Setting::get('school_name', 'Sistem Informasi Sekolah') }}</p>
            </div>

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autofocus
                        placeholder="guru@sekolah.sch.id"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border rounded-xl text-sm text-slate-900 placeholder-slate-400 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white
                               {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-200' }}"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border rounded-xl text-sm text-slate-900 placeholder-slate-400 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white
                                   {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-slate-200' }}"
                        >
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="remember" class="text-sm text-slate-600">Ingat saya</label>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Masuk
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-xs text-slate-400">
            © {{ date('Y') }} Agenda Mengajar Guru — Semua hak dilindungi
        </p>
    </div>

</body>
</html>
