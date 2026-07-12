@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium leading-5 text-blue-700 shadow-sm focus:outline-none focus:border-blue-400 transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-medium leading-5 text-gray-500 hover:border-blue-100 hover:bg-blue-50/70 hover:text-blue-600 focus:outline-none focus:text-blue-600 focus:border-blue-200 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
