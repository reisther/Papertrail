<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="profile_picture" :value="__('Profile Picture')" />
            <div class="mt-2 flex items-center gap-4">
                @if($user->profile_picture_path)
                    <img src="{{ route('profile.picture', $user) }}"
                         alt="{{ $user->name }}"
                         class="h-20 w-20 rounded-full object-cover border border-gray-200">
                @else
                    <div class="h-20 w-20 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-semibold">
                        {{ strtoupper(substr($user->firstname, 0, 1)) }}
                    </div>
                @endif
                <div class="flex-1">
                    <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100" />
                    <p class="mt-1 text-sm text-gray-500">JPG, PNG, or WebP up to 2MB.</p>
                    <x-input-error class="mt-2" :messages="$errors->get('profile_picture')" />
                </div>
            </div>
        </div>

        <!-- Name Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="firstname" :value="__('First Name')" />
                <x-text-input id="firstname" name="firstname" type="text" class="mt-1 block w-full" :value="old('firstname', $user->firstname)" required autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('firstname')" />
            </div>
            <div>
                <x-input-label for="lastname" :value="__('Last Name')" />
                <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full" :value="old('lastname', $user->lastname)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('lastname')" />
            </div>
        </div>

        <div>
            <x-input-label for="middlename" :value="__('Middle Name')" />
            <x-text-input id="middlename" name="middlename" type="text" class="mt-1 block w-full" :value="old('middlename', $user->middlename)" autocomplete="additional-name" />
            <x-input-error class="mt-2" :messages="$errors->get('middlename')" />
        </div>

 <!-- Academic Information -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-input-label for="campus" :value="__('Campus')" />
        <x-text-input id="campus" name="campus" type="text"
            class="mt-1 block w-full"
            :value="old('campus', $user->campus)"
            required />
    </div>

    <div>
        <x-input-label for="course" :value="__('Course')" />
        <x-text-input id="course" name="course" type="text"
            class="mt-1 block w-full"
            :value="old('course', $user->course)"
            required />
    </div>
</div>

{{-- Add the expertise section HERE --}}
@if($user->role === 'Teacher')

<div class="mt-6">
    <x-input-label :value="__('Areas of Expertise')" />

    @php
        $expertise = $user->expertise;
        $selectedExpertise = old('expertise', array_filter([
            optional($expertise)->machine_learning ? 'Machine Learning' : null,
            optional($expertise)->ai_integration ? 'AI Integration' : null,
            optional($expertise)->cybersecurity ? 'Cybersecurity' : null,
            optional($expertise)->iot ? 'IoT' : null,
            optional($expertise)->cloud_computing ? 'Cloud Computing' : null,
            optional($expertise)->data_analytics ? 'Data Analytics' : null,
            optional($expertise)->web_development ? 'Web Development' : null,
            optional($expertise)->mobile_development ? 'Mobile Development' : null,
            optional($expertise)->database_systems ? 'Database Systems' : null,
            optional($expertise)->networking ? 'Networking' : null,
        ]));
    @endphp

    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">

        <label class="flex items-center">
            <input type="checkbox"
                   name="expertise[]"
                   value="Machine Learning"
                   class="rounded border-gray-300"
                   @checked(in_array('Machine Learning', $selectedExpertise))>

            <span class="ml-2">Machine Learning</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox"
                   name="expertise[]"
                   value="AI Integration"
                   class="rounded border-gray-300"
                   @checked(in_array('AI Integration', $selectedExpertise))>

            <span class="ml-2">AI Integration</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox"
                   name="expertise[]"
                   value="Cybersecurity"
                   class="rounded border-gray-300"
                   @checked(in_array('Cybersecurity', $selectedExpertise))>

            <span class="ml-2">Cybersecurity</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox"
                   name="expertise[]"
                   value="IoT"
                   class="rounded border-gray-300"
                   @checked(in_array('IoT', $selectedExpertise))>

            <span class="ml-2">IoT</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox"
                   name="expertise[]"
                   value="Cloud Computing"
                   class="rounded border-gray-300"
                   @checked(in_array('Cloud Computing', $selectedExpertise))>

            <span class="ml-2">Cloud Computing</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox" name="expertise[]" value="Data Analytics" class="rounded border-gray-300" @checked(in_array('Data Analytics', $selectedExpertise))>
            <span class="ml-2">Data Analytics</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox" name="expertise[]" value="Web Development" class="rounded border-gray-300" @checked(in_array('Web Development', $selectedExpertise))>
            <span class="ml-2">Web Development</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox" name="expertise[]" value="Mobile Development" class="rounded border-gray-300" @checked(in_array('Mobile Development', $selectedExpertise))>
            <span class="ml-2">Mobile Development</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox" name="expertise[]" value="Database Systems" class="rounded border-gray-300" @checked(in_array('Database Systems', $selectedExpertise))>
            <span class="ml-2">Database Systems</span>
        </label>

        <label class="flex items-center">
            <input type="checkbox" name="expertise[]" value="Networking" class="rounded border-gray-300" @checked(in_array('Networking', $selectedExpertise))>
            <span class="ml-2">Networking</span>
        </label>

    </div>

    <div class="mt-4">
        <x-input-label for="custom_expertise" :value="__('Other Expertise')" />
        <textarea id="custom_expertise" name="custom_expertise" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="e.g., Blockchain, UI/UX Design, Natural Language Processing">{{ old('custom_expertise', implode(', ', optional($expertise)->custom_expertise ?? [])) }}</textarea>
        <p class="mt-1 text-sm text-gray-500">Separate multiple expertise areas with commas or new lines.</p>
        <x-input-error class="mt-2" :messages="$errors->get('custom_expertise')" />
    </div>
</div>

<div class="mt-6">
    <x-input-label for="adviser_schedule" :value="__('Schedule')" />
    @if($user->adviser_schedule_path)
        <div class="mt-2 rounded-lg border border-green-100 bg-green-50 p-3">
            <p class="text-sm font-medium text-green-900">{{ $user->adviser_schedule_name ?? 'Uploaded schedule' }}</p>
            <a href="{{ route('profile.adviser-schedule', $user) }}" target="_blank" rel="noopener"
               class="mt-1 inline-flex text-sm font-medium text-green-700 hover:text-green-900">
                View current schedule
            </a>
            <button type="button" onclick="openScheduleDeleteModal()" class="mt-3 inline-flex rounded-md bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                Delete Schedule
            </button>
        </div>
    @endif
    <input id="adviser_schedule" name="adviser_schedule" type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.doc,.docx"
           class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-green-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-green-700 hover:file:bg-green-100" />
    <p class="mt-1 text-sm text-gray-500">Upload a schedule image, PDF, or Word file up to 10MB.</p>
    <x-input-error class="mt-2" :messages="$errors->get('adviser_schedule')" />
</div>

@endif

<div>
    <x-input-label for="section" :value="__('Section')" />
    <x-text-input id="section" name="section" type="text"
        class="mt-1 block w-full"
        :value="old('section', $user->section)"
        required />
</div>

@if($user->isStudentGroupRole())
<div>
    <x-input-label for="student_number" :value="__('Student Number')" />
    <x-text-input id="student_number" name="student_number" type="text"
        class="mt-1 block w-full"
        :value="old('student_number', $user->student_number)"
        autocomplete="off" />
    <x-input-error class="mt-2" :messages="$errors->get('student_number')" />
</div>
@endif

<div>
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email"
        class="mt-1 block w-full"
        :value="old('email', $user->email)"
        required autocomplete="username" />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </div>
    </form>

    @if($user->isTeacher() && $user->adviser_schedule_path)
        <form id="deleteScheduleForm" method="POST" action="{{ route('profile.adviser-schedule.destroy') }}">
            @csrf
            @method('DELETE')
        </form>

        <div id="scheduleDeleteModal" class="fixed inset-0 z-50 hidden bg-gray-900/50 px-4 py-6">
            <div class="mx-auto mt-24 max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">Delete schedule?</h3>
                <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete your uploaded schedule? This action cannot be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeScheduleDeleteModal()" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" onclick="document.getElementById('deleteScheduleForm').submit()" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <script>
            function openScheduleDeleteModal() {
                document.getElementById('scheduleDeleteModal').classList.remove('hidden');
            }

            function closeScheduleDeleteModal() {
                document.getElementById('scheduleDeleteModal').classList.add('hidden');
            }
        </script>
    @endif
</section>
