<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Schedule Meeting</h2>
            <a href="{{ route('meeting-schedule.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700">
                Back to Calendar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('meeting-schedule.store') }}" class="space-y-6">
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
                            <label for="meeting_link" class="mb-2 block text-sm font-medium text-gray-700">Online Meeting Link</label>
                            <input id="meeting_link" name="meeting_link" type="text" value="{{ old('meeting_link') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="https://meet.google.com/abc-defg-hij">
                            <p class="mt-1 text-sm text-gray-500">Paste an existing meeting link.</p>
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
                            <a href="{{ route('meeting-schedule.index') }}" class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-400">Cancel</a>
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

        document.addEventListener('DOMContentLoaded', function() {
            const selectedDate = new URLSearchParams(window.location.search).get('date');
            if (selectedDate) {
                document.getElementById('start_time').value = selectedDate + 'T09:00';
                document.getElementById('end_time').value = selectedDate + 'T10:00';
            }
        });
    </script>
</x-app-layout>
