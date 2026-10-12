<?php

namespace App\Services;

use App\Models\AdminAccountRecovery;
use App\Models\AdviserStudent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountDeletionService
{
    public function deleteNow(User $user): bool
    {
        $user->forceFill(['deletion_scheduled_for' => now()->subSecond()])->save();

        return $this->purgeIfDue($user->id);
    }

    public function purgeDueAccounts(): int
    {
        $deleted = 0;

        User::query()
            ->whereNotNull('deletion_scheduled_for')
            ->where('deletion_scheduled_for', '<=', now())
            ->select('id')
            ->chunkById(100, function ($users) use (&$deleted): void {
                foreach ($users as $user) {
                    if ($this->purgeIfDue($user->id)) {
                        $deleted++;
                    }
                }
            });

        return $deleted;
    }

    private function purgeIfDue(int $userId): bool
    {
        $paths = DB::transaction(function () use ($userId): ?array {
            $user = User::query()->lockForUpdate()->find($userId);

            if (! $user || ! $user->deletion_scheduled_for || $user->deletion_scheduled_for->isFuture()) {
                return null;
            }

            $publicPaths = collect([
                $user->id_document_path,
                $user->profile_picture_path,
                $user->adviser_schedule_path,
            ])->filter()
                ->unique()
                ->values()
                ->all();

            $localPaths = AdminAccountRecovery::query()
                ->where('user_id', $user->id)
                ->pluck('temporary_document_path')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $ownedProjects = Project::query()->where('owner_id', $user->id)->with('chatRooms')->get();

            if ($user->isTeacher()) {
                // Adviser archives are relationships to student-owned originals, not separate files.
                AdviserStudent::query()->where('adviser_id', $user->id)->delete();
                Project::query()->where('adviser_id', $user->id)->update(['adviser_id' => null]);
                DB::table('chat_participants')->where('user_id', $user->id)->delete();
                DB::table('adviser_expertise')->where('adviser_id', $user->id)->delete();
            }

            if ($ownedProjects->isNotEmpty()) {
                AdviserStudent::query()
                    ->where('student_id', $user->id)
                    ->approved()
                    ->active()
                    ->update(['archived_at' => now()]);

                foreach ($ownedProjects as $project) {
                    $project->update(['status' => 'archived']);

                    if ($project->adviser_id && ($adviser = User::find($project->adviser_id))) {
                        $project->chatRooms
                            ->where('type', 'project')
                            ->each(fn ($room) => $room->addParticipant($adviser, 'moderator'));
                    }
                }
            }

            AdminAccountRecovery::query()->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', strtolower($user->email))->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('login_devices')->where('user_id', $user->id)->delete();
            DB::table('google_oauth_tokens')->where('user_id', $user->id)->delete();
            DB::table('app_notifications')->where('user_id', $user->id)->delete();
            DB::table('project_invitations')->where('created_by', $user->id)->delete();

            $user->forceFill([
                'firstname' => 'Deleted',
                'middlename' => null,
                'lastname' => 'User',
                'campus' => 'Deleted',
                'course' => 'Deleted',
                'section' => 'Deleted',
                'student_number' => null,
                'profile_picture_path' => null,
                'adviser_schedule_path' => null,
                'adviser_schedule_name' => null,
                'adviser_schedule_mime' => null,
                'id_document_path' => null,
                'status' => 'Deleted',
                'email' => 'deleted-'.$user->id.'-'.Str::uuid().'@invalid.local',
                'role' => 'Deleted',
                'password' => Hash::make(Str::random(64)),
                'remember_token' => Str::random(60),
                'email_verified_at' => null,
                'verified_at' => null,
                'verified_by' => null,
                'admin_notes' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'last_login_at' => null,
                'failed_login_attempts' => 0,
                'login_delay_until' => null,
                'locked_until' => null,
                'deletion_scheduled_for' => null,
            ])->save();
            $user->syncRoleProfile();

            return [$publicPaths, $localPaths];
        });

        if ($paths === null) {
            return false;
        }

        Storage::disk('public')->delete($paths[0]);
        Storage::disk('local')->delete($paths[1]);

        return true;
    }
}
