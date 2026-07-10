<x-guest-layout>
    <form method="POST" action="{{ route('register', [], false) }}" enctype="multipart/form-data">
        @csrf

        <div>
            <x-input-label for="role" :value="__('Sign up as')" />
            <select id="role" name="role" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-indigo-50">
                <option value="Student" {{ old('role') === 'Student' ? 'selected' : '' }}>Member</option>
                <option value="Leader" {{ old('role') === 'Leader' ? 'selected' : '' }}>Leader</option>
                <option value="Teacher" {{ old('role') === 'Teacher' ? 'selected' : '' }}>Adviser</option>
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="firstname" :value="__('First Name')" />
                <x-text-input id="firstname" class="block mt-1 w-full" type="text" name="firstname" :value="old('firstname')" required autofocus autocomplete="given-name" />
                <x-input-error :messages="$errors->get('firstname')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="lastname" :value="__('Last Name')" />
                <x-text-input id="lastname" class="block mt-1 w-full" type="text" name="lastname" :value="old('lastname')" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('lastname')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="middlename" :value="__('Middle Name')" />
            <x-text-input id="middlename" class="block mt-1 w-full" type="text" name="middlename" :value="old('middlename')" autocomplete="additional-name" />
            <x-input-error :messages="$errors->get('middlename')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="campus" :value="__('Campus')" />
                <x-text-input id="campus" class="block mt-1 w-full" type="text" name="campus" :value="old('campus')" required />
                <x-input-error :messages="$errors->get('campus')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="course" :value="__('Course')" />
                <x-text-input id="course" class="block mt-1 w-full" type="text" name="course" :value="old('course')" required />
                <x-input-error :messages="$errors->get('course')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="section" :value="__('Section')" />
            <x-text-input id="section" class="block mt-1 w-full" type="text" name="section" :value="old('section')" required />
            <x-input-error :messages="$errors->get('section')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="id_document_file" :value="__('ID Document Photo/PDF')" />
            <input id="id_document_file" name="id_document_file" type="file" accept=".jpg,.jpeg,.png,.pdf" required class="block mt-1 w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <x-input-error :messages="$errors->get('id_document_file')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4">
            <label class="flex items-start">
                <input type="checkbox" name="terms" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                <span class="ml-2 text-sm text-gray-600">
                    I agree to the
                    <a href="{{ route('terms') }}" target="_blank" class="underline text-indigo-600 hover:text-indigo-900">
                        Terms and Conditions
                    </a>
                    and Privacy Policy.
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
