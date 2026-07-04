@if($adviser?->adviser_schedule_path)
    <div class="{{ $class ?? 'mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4' }}">
        @if($collapsed ?? false)
            <details>
                <summary class="inline-flex cursor-pointer list-none items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                    Show Adviser's schedule
                </summary>
                <div class="mt-4">
        @endif

        <p class="text-sm font-semibold text-gray-900">Schedule</p>
        <p class="mt-1 text-sm text-gray-600">{{ $adviser->adviser_schedule_name ?? 'Adviser schedule' }}</p>

        @if(str_starts_with((string) $adviser->adviser_schedule_mime, 'image/'))
            <a href="{{ route('profile.adviser-schedule', $adviser) }}" target="_blank" rel="noopener" class="mt-3 block">
                <img src="{{ route('profile.adviser-schedule', $adviser) }}"
                     alt="{{ $adviser->name }} schedule"
                     class="max-h-64 w-auto max-w-full rounded-md border border-gray-200 object-contain">
            </a>
        @else
            <a href="{{ route('profile.adviser-schedule', $adviser) }}" target="_blank" rel="noopener"
               class="mt-3 inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                View Schedule
            </a>
        @endif

        @if($collapsed ?? false)
                </div>
            </details>
        @endif
    </div>
@else
    @if($showEmpty ?? false)
        <div class="{{ $class ?? 'mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4' }}">
            <p class="text-sm font-semibold text-gray-900">Schedule</p>
            <p class="mt-1 text-sm text-gray-600">No adviser schedule uploaded yet.</p>
        </div>
    @endif
@endif
