@php
    $user = Auth::user();
    $canConnectGoogleCalendar = $user && ($user->isTeacher() || $user->canLeadGroup());
    $googleCalendarConnected = false;

    if ($canConnectGoogleCalendar && \Illuminate\Support\Facades\Schema::hasTable('google_oauth_tokens')) {
        $googleCalendarQuery = \Illuminate\Support\Facades\DB::table('google_oauth_tokens')
            ->where('provider', 'google_calendar');

        if (\Illuminate\Support\Facades\Schema::hasColumn('google_oauth_tokens', 'user_id')) {
            $googleCalendarQuery->where('user_id', $user->id);
        }

        $googleCalendarConnected = $googleCalendarQuery->exists();
    }
@endphp

@if($canConnectGoogleCalendar)
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Google Calendar
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Connect your own Google account so meetings you create can generate Google Meet links from your calendar.
            </p>
        </header>

        <div class="mt-6 rounded-md border border-gray-200 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">
                        {{ $googleCalendarConnected ? 'Connected' : 'Not connected' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $googleCalendarConnected ? 'PaperTrail can create Google Meet links using your Google Calendar.' : 'Connect before choosing Google Meet auto-create.' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('setup-google-auth') }}"
                       class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        {{ $googleCalendarConnected ? 'Reconnect Google' : 'Connect Google' }}
                    </a>

                    @if($googleCalendarConnected)
                        <form method="POST" action="{{ route('google-calendar.disconnect') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Disconnect
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
