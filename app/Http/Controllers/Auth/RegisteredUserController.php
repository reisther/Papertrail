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
        $request->validate([
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
        ]);

        // Handle file upload
        $idDocumentPath = null;
        if ($request->hasFile('id_document_file')) {
            $file = $request->file('id_document_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $idDocumentPath = $file->storeAs('id_documents', $filename, 'public');
        }

        $user = User::create([
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'campus' => $request->campus,
            'course' => $request->course,
            'section' => $request->section,
            'id_document_path' => $idDocumentPath,
            'status' => 'Pending', // Default status
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
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

        // Don't auto-login since account needs admin verification
        // Auth::login($user);

        return redirect()->route('registration.success');
    }
}
