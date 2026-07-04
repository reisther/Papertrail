<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isEditing ? __('Edit Group Details') : __('Group Details') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg border p-6">
                @if($isEditing)
                    <form method="POST" action="{{ route('group-description.update') }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Group Details</h3>
                            <p class="mt-1 text-sm text-gray-600">Create or update your group information.</p>
                        </div>

                        <div>
                            <label for="group_name" class="block text-sm font-medium text-gray-700">Group Name</label>
                            <input id="group_name" name="group_name" type="text" required
                                   value="{{ old('group_name', $group?->title ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('group_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="group_course" class="block text-sm font-medium text-gray-700">Group Course</label>
                            @php
                                $selectedCourse = old('group_course', $group?->group_course ?? $group?->owner?->course ?? auth()->user()?->course);
                            @endphp
                            <select id="group_course" name="group_course" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach(['Information Technology', 'Information Systems', 'Computer Science'] as $course)
                                    <option value="{{ $course }}" @selected($selectedCourse === $course)>
                                        {{ $course }}
                                    </option>
                                @endforeach
                            </select>
                            @error('group_course') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="group_description" class="block text-sm font-medium text-gray-700">Group Description</label>
                            <textarea id="group_description" name="group_description" rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('group_description', $group?->description ?? '') }}</textarea>
                            @error('group_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-2">
                            @if($group)
                                <a href="{{ route('group-description.show') }}"
                                   class="inline-flex items-center bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 px-4 py-2 rounded-md text-sm font-medium">
                                    Cancel
                                </a>
                            @endif
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                {{ $group ? 'Save Changes' : 'Create Group' }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="space-y-6">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Group Details</h3>
                                <p class="mt-1 text-sm text-gray-600">View-only group information.</p>
                            </div>
                            @if($canManageGroup && $group)
                                <a href="{{ route('group-description.show', ['edit' => 1]) }}"
                                   class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Edit
                                </a>
                            @endif
                        </div>

                        @if($group)
                            <div>
                                <p class="text-sm font-medium text-gray-700">Group Name</p>
                                <p class="mt-1 text-gray-900">{{ $group->title }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-700">Group Course</p>
                                <p class="mt-1 text-gray-900">{{ $group->group_course ?? $group->owner?->course ?? 'Not set' }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-700">Group Description</p>
                                <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $group->description ?: 'No description yet.' }}</p>
                            </div>
                        @else
                            <p class="text-sm text-gray-600">You're not in a group yet. Ask your leader to send you the invitation link for your group.</p>
                        @endif
                    </div>
                @endif
            </div>

            @if($group)
                <div class="bg-white shadow-sm rounded-lg border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Leader</h3>

                    @if($group->owner)
                        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                            <div class="flex items-center gap-4">
                                @include('partials.user-avatar', ['user' => $group->owner, 'size' => 'h-14 w-14', 'textSize' => 'text-base', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'])
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900">{{ $group->owner->name }}</p>
                                    <p class="text-sm text-blue-700 mt-1">{{ $group->owner->course }} - {{ $group->owner->section }}</p>
                                    @if($group->owner->student_number)
                                        <p class="text-sm text-blue-700 mt-1">Student No. {{ $group->owner->student_number }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-600">No leader assigned.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Adviser</h3>

                    @if($approvedAdviser)
                        <div class="rounded-lg border border-green-100 bg-green-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-4">
                                    @include('partials.user-avatar', ['user' => $approvedAdviser, 'size' => 'h-14 w-14', 'textSize' => 'text-base', 'bg' => 'bg-green-100', 'text' => 'text-green-700'])
                                    <div class="min-w-0">
                                        <p class="font-medium text-green-900">{{ $approvedAdviser->name }}</p>
                                        <p class="text-sm text-green-700 mt-1">{{ $approvedAdviser->course }}</p>
                                        <p class="text-xs text-green-700 mt-2">Approved adviser</p>
                                    </div>
                                </div>
                                @if($canManageGroup && $isEditing && $approvedAdviserRelationship)
                                    <form method="POST" action="{{ route('advisers.release', $approvedAdviserRelationship) }}" onsubmit="return confirm('Remove this adviser from the group?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-md text-sm font-medium">
                                            Remove
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @include('partials.adviser-schedule', [
                            'adviser' => $approvedAdviser,
                            'class' => 'mt-4 rounded-lg border border-green-100 bg-white/70 p-4',
                        ])
                    @else
                        <p class="text-sm text-gray-600">
                            No approved adviser yet.
                            @if($canManageGroup && $isEditing)
                                You can use Find Advisers after creating your group.
                            @endif
                        </p>
                    @endif
                </div>

                @if($canManageGroup && $isEditing)
                    <div class="bg-white shadow-sm rounded-lg border p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Share Group Link</h3>
                                <p class="text-sm text-gray-600 mt-1">Send this link to members so they can join your group.</p>
                            </div>
                            <form method="POST" action="{{ route('group-description.share-link') }}">
                                @csrf
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Generate Link
                                </button>
                            </form>
                        </div>

                        @php
                            $shownInviteLink = session('invite_link') ?? ($activeInvitation ? route('projects.accept-invitation', $activeInvitation->token) : null);
                        @endphp

                        @if($shownInviteLink)
                            <div class="mt-4">
                                <label for="groupInviteLink" class="block text-sm font-medium text-gray-700 mb-2">Invitation Link</label>
                                <div class="flex gap-2">
                                    <input id="groupInviteLink" type="text" readonly value="{{ $shownInviteLink }}"
                                           class="flex-1 rounded-md border-gray-300 bg-gray-50 text-sm">
                                    <button type="button" onclick="copyGroupInviteLink()"
                                            class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="bg-white shadow-sm rounded-lg border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Members</h3>

                    <div class="divide-y divide-gray-200">
                        @forelse($group->members as $member)
                            <div class="py-3 flex items-center justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    @include('partials.user-avatar', ['user' => $member, 'size' => 'h-11 w-11', 'textSize' => 'text-sm', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'])
                                    <div class="min-w-0">
                                        <p class="font-medium text-blue-900">{{ $member->name }}</p>
                                        <p class="text-sm text-blue-700">{{ $member->course }} - {{ $member->section }}</p>
                                        @if($member->student_number)
                                            <p class="text-sm text-blue-700">Student No. {{ $member->student_number }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($canManageGroup && $isEditing)
                                    <form method="POST" action="{{ route('group-description.members.remove', $member) }}" onsubmit="return confirm('Remove this member from the group?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-md text-sm font-medium">
                                            Kick
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="py-3">
                                <p class="text-sm text-gray-600">No members have joined yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function copyGroupInviteLink() {
            const input = document.getElementById('groupInviteLink');
            if (!input) {
                return;
            }

            input.select();
            document.execCommand('copy');
        }
    </script>
</x-app-layout>
