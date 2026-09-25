<section>
    <header class="profile-panel-heading">
        <span class="profile-panel-icon bg-blue-50 text-blue-600" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.5 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5a3.5 3.5 0 0 0-3.5 3.5V20M9.5 11.5A3.5 3.5 0 1 0 9.5 4a3.5 3.5 0 0 0 0 7.5ZM17 8h4m-2-2v4" /></svg>
        </span>
        <div>
            <h2>{{ __('Profile information') }}</h2>
            <p>{{ __("Update the details people see when they work with you.") }}</p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="profile-form">
        @csrf
        @method('patch')

        <div class="profile-form-section">
            <div class="profile-field-heading">
                <x-input-label for="profile_picture" :value="__('Profile photo')" />
                <p>Help teammates recognize you at a glance.</p>
            </div>
            <div class="profile-photo-field">
                @if($user->profile_picture_path)
                    <img src="{{ route('profile.picture', $user) }}"
                         alt="{{ $user->name }}"
                         class="profile-photo-preview">
                @else
                    <div class="profile-photo-preview profile-photo-placeholder">
                        {{ strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp"
                           class="profile-file-input" />
                    <p class="profile-field-help">JPG, PNG, or WebP · maximum 2 MB</p>
                    <x-input-error class="mt-2" :messages="$errors->get('profile_picture')" />
                </div>
            </div>
        </div>

        <div class="profile-form-section">
            <div class="profile-field-heading">
                <h3>Personal details</h3>
                <p>Use the name and academic details associated with your account.</p>
            </div>
        <div class="profile-fields profile-fields-two">
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

        <div class="profile-fields profile-fields-two mt-5">
        <div>
            <x-input-label for="middlename" :value="__('Middle Name')" />
            <x-text-input id="middlename" name="middlename" type="text" class="mt-1 block w-full" :value="old('middlename', $user->middlename)" autocomplete="additional-name" />
            <x-input-error class="mt-2" :messages="$errors->get('middlename')" />
        </div>
        <div>
            <x-input-label for="section" :value="__('Section')" />
            <x-text-input id="section" name="section" type="text" class="mt-1 block w-full" :value="old('section', $user->section)" required />
            <x-input-error class="mt-2" :messages="$errors->get('section')" />
        </div>
        </div>

<div class="profile-fields profile-fields-two mt-5">
    <div>
        <x-input-label for="campus" :value="__('Campus')" />
        <x-text-input id="campus" name="campus" type="text"
            class="mt-1 block w-full"
            :value="old('campus', $user->campus)"
            required />
        <x-input-error class="mt-2" :messages="$errors->get('campus')" />
    </div>

    <div>
        <x-input-label for="course" :value="__('Course')" />
        <x-text-input id="course" name="course" type="text"
            class="mt-1 block w-full"
            :value="old('course', $user->course)"
            required />
        <x-input-error class="mt-2" :messages="$errors->get('course')" />
    </div>
</div>
        </div>

{{-- Add the expertise section HERE --}}
@if($user->role === 'Teacher')

<div class="profile-form-section">
    <div class="profile-field-heading">
        <x-input-label :value="__('Areas of expertise')" />
        <p>Select the subjects you are happy to advise on.</p>
    </div>

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

    <div class="profile-expertise-grid">

        <label class="profile-choice">
            <input type="checkbox"
                   name="expertise[]"
                   value="Machine Learning"
                   class="rounded border-gray-300"
                   @checked(in_array('Machine Learning', $selectedExpertise))>

            <span>Machine Learning</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox"
                   name="expertise[]"
                   value="AI Integration"
                   class="rounded border-gray-300"
                   @checked(in_array('AI Integration', $selectedExpertise))>

            <span>AI Integration</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox"
                   name="expertise[]"
                   value="Cybersecurity"
                   class="rounded border-gray-300"
                   @checked(in_array('Cybersecurity', $selectedExpertise))>

            <span>Cybersecurity</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox"
                   name="expertise[]"
                   value="IoT"
                   class="rounded border-gray-300"
                   @checked(in_array('IoT', $selectedExpertise))>

            <span>IoT</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox"
                   name="expertise[]"
                   value="Cloud Computing"
                   class="rounded border-gray-300"
                   @checked(in_array('Cloud Computing', $selectedExpertise))>

            <span>Cloud Computing</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox" name="expertise[]" value="Data Analytics" class="rounded border-gray-300" @checked(in_array('Data Analytics', $selectedExpertise))>
            <span>Data Analytics</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox" name="expertise[]" value="Web Development" class="rounded border-gray-300" @checked(in_array('Web Development', $selectedExpertise))>
            <span>Web Development</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox" name="expertise[]" value="Mobile Development" class="rounded border-gray-300" @checked(in_array('Mobile Development', $selectedExpertise))>
            <span>Mobile Development</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox" name="expertise[]" value="Database Systems" class="rounded border-gray-300" @checked(in_array('Database Systems', $selectedExpertise))>
            <span>Database Systems</span>
        </label>

        <label class="profile-choice">
            <input type="checkbox" name="expertise[]" value="Networking" class="rounded border-gray-300" @checked(in_array('Networking', $selectedExpertise))>
            <span>Networking</span>
        </label>

    </div>

    <div class="mt-5">
        <x-input-label for="custom_expertise" :value="__('Other Expertise')" />
        <textarea id="custom_expertise" name="custom_expertise" rows="3"
                  class="mt-1 block w-full"
                  placeholder="e.g., Blockchain, UI/UX Design, Natural Language Processing">{{ old('custom_expertise', implode(', ', optional($expertise)->custom_expertise ?? [])) }}</textarea>
        <p class="profile-field-help">Separate multiple areas with commas or new lines.</p>
        <x-input-error class="mt-2" :messages="$errors->get('custom_expertise')" />
    </div>
</div>

<div class="profile-form-section">
    <div class="profile-field-heading">
        <x-input-label for="adviser_schedule" :value="__('Availability schedule')" />
        <p>Share your current schedule so students can plan ahead.</p>
    </div>
    @if($user->adviser_schedule_path)
        <div class="profile-schedule-current">
            <p class="text-sm font-medium text-green-900">{{ $user->adviser_schedule_name ?? 'Uploaded schedule' }}</p>
            <a href="{{ route('profile.adviser-schedule', $user) }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                View current schedule
            </a>
            <button type="button" onclick="openScheduleDeleteModal()" class="mt-3 inline-flex rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                Delete Schedule
            </button>
        </div>
    @endif
    <input id="adviser_schedule" name="adviser_schedule" type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.doc,.docx"
           class="profile-file-input profile-file-input-success" />
    <p class="profile-field-help">Image, PDF, or Word document · maximum 10 MB</p>
    <x-input-error class="mt-2" :messages="$errors->get('adviser_schedule')" />
</div>

@endif

@if($user->isStudentGroupRole())
<div class="profile-form-section">
    <x-input-label for="student_number" :value="__('Student Number')" />
    <x-text-input id="student_number" name="student_number" type="text"
        class="mt-1 block w-full"
        :value="old('student_number', $user->student_number)"
        autocomplete="off" />
    <x-input-error class="mt-2" :messages="$errors->get('student_number')" />
</div>
@endif

<div class="profile-form-section">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" name="email" type="email"
        class="mt-1 block w-full"
        :value="old('email', $user->email)"
        required autocomplete="username" />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

        <div class="profile-form-actions">
            <p>Changes are saved to your PaperTrail account.</p>
            <x-primary-button>{{ __('Save changes') }}</x-primary-button>
        </div>
    </form>

    @if($user->isTeacher() && $user->adviser_schedule_path)
        <form id="deleteScheduleForm" method="POST" action="{{ route('profile.adviser-schedule.destroy') }}">
            @csrf
            @method('DELETE')
        </form>

        <div id="scheduleDeleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 px-4 py-6">
            <div class="mx-auto mt-12 max-w-md rounded-2xl bg-white p-5 shadow-xl sm:mt-24 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-900">Delete schedule?</h3>
                <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete your uploaded schedule? This action cannot be undone.</p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
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
