<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administrative Account Recovery</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="mb-6 text-sm text-gray-600">
                    Recovering <strong>{{ $user->name }}</strong> ({{ $user->email }}). Verify identity using institutional records and only the minimum necessary documents. Never request or assign the permanent password.
                </p>

                <form method="POST" action="{{ route('admin.account-recovery.store', $user) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="new_email" :value="__('Verified new email address')" />
                        <x-text-input id="new_email" class="block mt-1 w-full" type="email" name="new_email" :value="old('new_email')" required />
                        <x-input-error :messages="$errors->get('new_email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="verification_channel" :value="__('Approved verification channel')" />
                        <select id="verification_channel" name="verification_channel" class="block mt-1 w-full rounded-md border-gray-300" required>
                            <option value="secure_channel">Approved secure channel</option>
                            <option value="in_person">In person</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="reason" :value="__('Recovery reason and verification record')" />
                        <textarea id="reason" name="reason" rows="5" class="block mt-1 w-full rounded-md border-gray-300" required>{{ old('reason') }}</textarea>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="verification_document" :value="__('Temporary verification document (optional)')" />
                        <input id="verification_document" type="file" name="verification_document" accept=".pdf,.jpg,.jpeg,.png" class="block mt-1 w-full text-sm" />
                        <p class="mt-1 text-xs text-gray-500">Stored encrypted and automatically deleted after the configured retention period.</p>
                        <x-input-error :messages="$errors->get('verification_document')" class="mt-2" />
                    </div>

                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="identity_verified" value="1" required class="mt-1 rounded border-gray-300" />
                        <span>I confirm that I am authorized and verified the claimant against institutional records using the minimum necessary evidence.</span>
                    </label>
                    <x-input-error :messages="$errors->get('identity_verified')" class="mt-2" />

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.all-users') }}" class="px-4 py-2 text-sm text-gray-700">Cancel</a>
                        <x-primary-button>{{ __('Complete Recovery') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
