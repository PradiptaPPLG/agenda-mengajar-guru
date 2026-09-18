@props(['href', 'active' => false])

<a href="{{ $href }}"
   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
          {{ $active
              ? 'bg-blue-50 text-blue-700'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
    {{ $slot }}
</a>
