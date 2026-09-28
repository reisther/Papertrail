<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'id_document_file' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'], // 10MB max
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:Student,Leader,Teacher'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ], [
            'email.unique' => 'This email address already has a PaperTrail account. Please sign in instead or use a different email address.',
        ])->validateWithBag('registration');

        // Handle file upload
        $idDocumentPath = null;
        if ($request->hasFile('id_document_file')) {
            $file = $request->file('id_document_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $idDocumentPath = $file->storeAs('id_documents', $filename, 'public');
        }

        $user = User::create([
            'firstname' => $validated['firstname'],
            'middlename' => $validated['middlename'] ?? null,
            'lastname' => $validated['lastname'],
            'campus' => $validated['campus'],
            'course' => $validated['course'],
            'section' => $validated['section'],
            'id_document_path' => $idDocumentPath,
            'status' => 'Pending', // Default status
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->syncRoleProfile();

        event(new Registered($user));

        if (Schema::hasTable('app_notifications')) {
            User::where('role', 'Admin')->get()->each(function (User $admin) use ($user) {
                AppNotification::firstOrCreate(
                    [
                        'user_id' => $admin->id,
                        'type' => 'admin_signup',
                        'source_type' => 'user',
                        'source_id' => $user->id,
                    ],
                    [
                        'title' => 'New sign-up pending',
                        'body' => "{$user->name} submitted an account verification request as {$user->role_display_name}.",
                        'action_url' => route('admin.view-user', $user),
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            });
        }

        try {
            app(EmailNotificationService::class)->sendUserRegistrationPending($user);
        } catch (\Throwable $exception) {
            Log::warning('Failed to send admin registration notification email.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $confirmationSent = false;
        try {
            $confirmationSent = app(EmailNotificationService::class)->sendRegistrationReceived($user);
        } catch (\Throwable $exception) {
            Log::warning('Failed to send registration confirmation email.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        // Don't auto-login since account needs admin verification
        // Auth::login($user);

        return redirect()->route('home')->with([
            'registration_pending' => ['email' => $user->email],
            'registration_confirmation_sent' => $confirmationSent,
        ]);
    }
}
