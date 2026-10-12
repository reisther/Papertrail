@props(['requirementsId'])

<div {{ $attributes }} x-data="{
    password: '',
    get checks() {
        return {
            length: [...this.password].length >= 8,
            uppercase: /\p{Lu}/u.test(this.password),
            lowercase: /\p{Ll}/u.test(this.password),
            number: /\p{N}/u.test(this.password),
            special: /[\p{Z}\p{S}\p{P}]/u.test(this.password)
        };
    },
    get valid() { return Object.values(this.checks).every(Boolean); }
}">
    {{ $slot }}

    <div id="{{ $requirementsId }}" class="mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs" aria-label="Password requirements">
        <p class="font-medium text-gray-700">Password must include:</p>
        <ul class="mt-1 grid gap-1 sm:grid-cols-2">
            @foreach (['length' => 'At least 8 characters', 'uppercase' => 'An uppercase letter', 'lowercase' => 'A lowercase letter', 'number' => 'A number', 'special' => 'A special character'] as $rule => $label)
                <li x-bind:class="password === '' ? 'text-gray-600' : checks.{{ $rule }} ? 'text-green-700' : 'text-red-700'">
                    <span aria-hidden="true" x-text="checks.{{ $rule }} ? '✓' : '○'">○</span>
                    {{ $label }}
                </li>
            @endforeach
        </ul>
        <p class="mt-2 font-medium" role="status" aria-live="polite"
           x-bind:class="password === '' ? 'text-gray-600' : valid ? 'text-green-700' : 'text-red-700'"
           x-text="password === '' ? 'Start typing to check your password.' : valid ? 'Password meets all requirements.' : 'Password does not meet all requirements yet.'">Start typing to check your password.</p>
    </div>
</div>
