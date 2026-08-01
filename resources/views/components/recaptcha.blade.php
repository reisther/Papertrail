@if(config('services.recaptcha.site_key'))
    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
    @once
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endonce
@else
    <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        Security verification is temporarily unavailable. Please contact an administrator.
    </div>
@endif

<x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-2" />
