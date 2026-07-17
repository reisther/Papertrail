<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Meeting</h2>
            <a href="{{ route('meeting-schedule.show', $meetingSchedule) }}" class="inline-flex items-center justify-center rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700">
                Back to Details
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('meeting-schedule.update', $meetingSchedule) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="title" class="mb-2 block text-sm font-medium text-gray-700">
                                Meeting Title <span class="text-red-500">*</span>
                            </label>
                            <input id="title" name="title" type="text" value="{{ old('title', $meetingSchedule->title) }}" required
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                                    <option value="{{ $project->id }}" @selected(old('project_id', $meetingSchedule->project_id) == $project->id)>
                                        {{ $project->title }} @if($project->owner) - {{ $project->owner->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="type" class="mb-2 block text-sm font-medium text-gray-700">
                                    Type of Meeting <span class="text-red-500">*</span>
                                </label>
                                <select id="type" name="type" required
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select type</option>
                                    <option value="meeting" @selected(old('type', $meetingSchedule->type) === 'meeting')>Meeting</option>
                                    <option value="consultation" @selected(old('type', $meetingSchedule->type) === 'consultation')>Consultation</option>
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="scheduled" @selected(old('status', $meetingSchedule->status) === 'scheduled')>Scheduled</option>
                                    <option value="completed" @selected(old('status', $meetingSchedule->status) === 'completed')>Completed</option>
                                    <option value="cancelled" @selected(old('status', $meetingSchedule->status) === 'cancelled')>Cancelled</option>
                                    <option value="rescheduled" @selected(old('status', $meetingSchedule->status) === 'rescheduled')>Rescheduled</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="start_time" class="mb-2 block text-sm font-medium text-gray-700">
                                    Start Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input id="start_time" name="start_time" type="datetime-local" value="{{ old('start_time', $meetingSchedule->start_time->format('Y-m-d\TH:i')) }}" required
                                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('start_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="end_time" class="mb-2 block text-sm font-medium text-gray-700">
                                    End Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input id="end_time" name="end_time" type="datetime-local" value="{{ old('end_time', $meetingSchedule->end_time->format('Y-m-d\TH:i')) }}" required
                                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('end_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="meeting_link" class="mb-2 block text-sm font-medium text-gray-700">Online Meeting Link</label>
                            <input id="meeting_link" name="meeting_link" type="text" value="{{ old('meeting_link', $meetingSchedule->meeting_link) }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="https://meet.google.com/abc-defg-hij">
                            @error('meeting_link')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="mb-2 block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $meetingSchedule->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end space-x-3 border-t border-gray-200 pt-6">
                            <a href="{{ route('meeting-schedule.show', $meetingSchedule) }}" class="rounded-md bg-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-400">Cancel</a>
                            <button type="submit" class="rounded-md bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700">Update Meeting</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
