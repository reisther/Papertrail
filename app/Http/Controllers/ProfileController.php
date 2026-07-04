<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AdviserStudent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Serve profile pictures without depending on the public storage symlink.
     */
    public function picture(User $user)
    {
        abort_unless(Auth::check(), 403);
        abort_unless($user->profile_picture_path, 404);
        abort_unless(Storage::disk('public')->exists($user->profile_picture_path), 404);

        return response()->file(Storage::disk('public')->path($user->profile_picture_path));
    }

    public function adviserSchedule(User $user)
    {
        abort_unless(Auth::check(), 403);
        abort_unless($user->isTeacher(), 404);
        abort_unless($this->canViewAdviserSchedule($user), 403);
        abort_unless($user->adviser_schedule_path, 404);
        abort_unless(Storage::disk('public')->exists($user->adviser_schedule_path), 404);

        return response()->file(Storage::disk('public')->path($user->adviser_schedule_path), [
            'Content-Type' => $user->adviser_schedule_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($user->adviser_schedule_name ?: 'schedule') . '"',
        ]);
    }

    public function destroyAdviserSchedule(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isTeacher(), 403);

        if ($user->adviser_schedule_path) {
            Storage::disk('public')->delete($user->adviser_schedule_path);
        }

        $user->update([
            'adviser_schedule_path' => null,
            'adviser_schedule_name' => null,
            'adviser_schedule_mime' => null,
        ]);

        return Redirect::route('profile.edit')->with('success', 'Schedule deleted successfully.');
    }


    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $expertise = $validated['expertise'] ?? [];
        $customExpertise = collect(preg_split('/[\r\n,]+/', $validated['custom_expertise'] ?? ''))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique(fn ($item) => mb_strtolower($item))
            ->values()
            ->all();

        unset($validated['expertise']);
        unset($validated['custom_expertise']);
        unset($validated['profile_picture']);
        unset($validated['adviser_schedule']);
        unset($validated['delete_adviser_schedule']);

        if ($request->hasFile('profile_picture')) {
            if ($request->user()->profile_picture_path) {
                Storage::disk('public')->delete($request->user()->profile_picture_path);
            }

            $validated['profile_picture_path'] = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        if ($request->user()->isTeacher() && $request->hasFile('adviser_schedule')) {
            if ($request->user()->adviser_schedule_path) {
                Storage::disk('public')->delete($request->user()->adviser_schedule_path);
            }

            $scheduleFile = $request->file('adviser_schedule');
            $validated['adviser_schedule_path'] = $scheduleFile->store('adviser_schedules', 'public');
            $validated['adviser_schedule_name'] = $scheduleFile->getClientOriginalName();
            $validated['adviser_schedule_mime'] = $scheduleFile->getMimeType();
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($request->user()->isTeacher()) {
            $request->user()->expertise()->updateOrCreate(
                ['adviser_id' => $request->user()->id],
                [
                    'machine_learning' => in_array('Machine Learning', $expertise, true),
                    'ai_integration' => in_array('AI Integration', $expertise, true),
                    'cybersecurity' => in_array('Cybersecurity', $expertise, true),
                    'iot' => in_array('IoT', $expertise, true),
                    'cloud_computing' => in_array('Cloud Computing', $expertise, true),
                    'data_analytics' => in_array('Data Analytics', $expertise, true),
                    'web_development' => in_array('Web Development', $expertise, true),
                    'mobile_development' => in_array('Mobile Development', $expertise, true),
                    'database_systems' => in_array('Database Systems', $expertise, true),
                    'networking' => in_array('Networking', $expertise, true),
                    'custom_expertise' => $customExpertise,
                ]
            );
        }

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return Redirect::route('profile.edit')->with('error', 'Admin accounts cannot delete themselves.');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->profile_picture_path) {
            Storage::disk('public')->delete($user->profile_picture_path);
        }

        if ($user->adviser_schedule_path) {
            Storage::disk('public')->delete($user->adviser_schedule_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function canViewAdviserSchedule(User $adviser): bool
    {
        $viewer = Auth::user();

        if (! $viewer || $viewer->isAdmin()) {
            return false;
        }

        if ($viewer->id === $adviser->id) {
            return true;
        }

        if ($viewer->canLeadGroup()) {
            return AdviserStudent::where('student_id', $viewer->id)
                ->where('adviser_id', $adviser->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
        }

        if ($viewer->isStudent()) {
            return Project::where('adviser_id', $adviser->id)
                ->whereHas('members', fn ($members) => $members->where('users.id', $viewer->id))
                ->exists();
        }

        return false;
    }
}
