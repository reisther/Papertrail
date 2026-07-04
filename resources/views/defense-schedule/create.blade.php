<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Schedule Meeting</h2>
            <a href="{{ route('defense-schedule.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700">
                Back to Calendar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('defense-schedule.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="title" class="mb-2 block text-sm font-medium text-gray-700">
                                Meeting Title <span class="text-red-500">*</span>
                            </label>
                            <input id="title" name="title" type="text" value="{{ old('title') }}" required
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., Chapter 1 consultation">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="project_id" class="mb-2 block text-sm font-medium text-gray-700">
                                Group Code <span class="text-red-500">*</span>
                            </label>
                            <select id="project_id" name="project_id" required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select group code</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>
                                        {{ $project->title }} @if($project->owner) - {{ $project->owner->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="mb-2 block text-sm font-medium text-gray-700">
                                Type of Meeting <span class="text-red-500">*</span>
                            </label>
                            <select id="type" name="type" required
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select type</option>
                                <option value="meeting" @selected(old('type') === 'meeting')>Meeting</option>
                                <option value="consultation" @selected(old('type') === 'consultation')>Consultation</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="start_time" class="mb-2 block text-sm font-medium text-gray-700">
                                    Start Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input id="start_time" name="start_time" type="datetime-local" value="{{ old('start_time') }}" required
                                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('start_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="end_time" class="mb-2 block text-sm font-medium text-gray-700">
                                    End Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input id="end_time" name="end_time" type="datetime-local" value="{{ old('end_time') }}" required
                                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('end_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Meeting Platform</label>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" name="meeting_platform" value="manual" @checked(old('meeting_platform', 'manual') === 'manual') class="border-gray-300 text-blue-600" onchange="toggleMeetingOptions()">
                                    <span class="ml-2 text-sm text-gray-700">Manual Link Entry</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="meeting_platform" value="google_meet" @checked(old('meeting_platform') === 'google_meet') class="border-gray-300 text-blue-600" onchange="toggleMeetingOptions()">
                                    <span class="ml-2 text-sm text-gray-700">Google Meet (create real Meet link)</span>
                                </label>
                            </div>
                            @error('meeting_platform')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @php
                            $googleCalendarConnected = false;

                            if (\Illuminate\Support\Facades\Schema::hasTable('google_oauth_tokens')) {
                                $googleCalendarQuery = \Illuminate\Support\Facades\DB::table('google_oauth_tokens')
                                    ->where('provider', 'google_calendar');

                                if (\Illuminate\Support\Facades\Schema::hasColumn('google_oauth_tokens', 'user_id')) {
                                    $googleCalendarQuery->where('user_id', Auth::id());
                                }

                                $googleCalendarConnected = $googleCalendarQuery->exists();
                            }
                        @endphp

                        <div id="google-meet-options" class="hidden rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    Google Calendar will create the actual Meet link and send invites to the adviser and group members.
                                    <div class="mt-1 font-medium">
                                        Status: {{ $googleCalendarConnected ? 'Google Calendar connected' : 'Google Calendar not connected' }}
                                    </div>
                                </div>

                                @if(! $googleCalendarConnected)
                                    <a href="{{ route('setup-google-auth') }}"
                                       class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-blue-700">
                                        Connect Google
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div id="manual-meeting-link">
                            <label for="meeting_link" class="mb-2 block text-sm font-medium text-gray-700">Online Meeting Link</label>
                            <input id="meeting_link" name="meeting_link" type="text" value="{{ old('meeting_link') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="https://meet.google.com/abc-defg-hij">
                            <p class="mt-1 text-sm text-gray-500">Paste an existing real meeting link. Google Meet links only work when Google creates them.</p>
                            @error('meeting_link')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="mb-2 block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Additional details for the meeting">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3 border-t border-gray-200 pt-6">
                            <a href="{{ route('defense-schedule.index') }}" class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-400">Cancel</a>
                            <button type="submit" class="rounded-md bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700">Schedule Meeting</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = new Date(this.value);
            if (startTime) {
                const endTime = new Date(startTime.getTime() + (60 * 60 * 1000));
                document.getElementById('end_time').value = endTime.toISOString().slice(0, 16);
            }
        });

        function toggleMeetingOptions() {
            const googleMeetRadio = document.querySelector('input[name="meeting_platform"][value="google_meet"]');
            document.getElementById('google-meet-options').classList.toggle('hidden', !googleMeetRadio.checked);
            document.getElementById('manual-meeting-link').classList.toggle('hidden', googleMeetRadio.checked);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectedDate = new URLSearchParams(window.location.search).get('date');
            if (selectedDate) {
                document.getElementById('start_time').value = selectedDate + 'T09:00';
                document.getElementById('end_time').value = selectedDate + 'T10:00';
            }
            toggleMeetingOptions();
        });
    </script>
</x-app-layout>
