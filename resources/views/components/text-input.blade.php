@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-slate-300 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500']) }}>
