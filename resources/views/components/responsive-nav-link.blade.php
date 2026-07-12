@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-r-md ps-3 pe-4 py-2 border-l-4 border-blue-500 text-start text-base font-medium text-blue-700 bg-blue-50 focus:outline-none focus:text-blue-800 focus:bg-blue-100 focus:border-blue-600 transition duration-150 ease-in-out'
            : 'block w-full rounded-r-md ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50/70 hover:border-blue-200 focus:outline-none focus:text-blue-600 focus:bg-blue-50 focus:border-blue-200 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
