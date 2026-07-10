<?php

namespace App\Services;

use App\Mail\PaperTrailNotification;
use App\Models\AdviserStudent;
use App\Models\Announcement;
use App\Models\ChatRoom;
use App\Models\DefenseSchedule;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public function sendAnnouncementPosted(Announcement $announcement): int
    {
        $announcement->loadMissing('author', 'project.owner', 'project.members', 'project.adviser');

        $subject = match ($announcement->audience_type) {
            'global' => 'PaperTrail: Admin announcement',
            'adviser_students' => 'PaperTrail: Adviser announcement',
            'project' => 'PaperTrail: Group announcement',
            default => 'PaperTrail: Announcement',
        };
        $reason = match ($announcement->audience_type) {
            'global' => 'You are receiving this because you have a PaperTrail account.',
            'adviser_students' => 'You are receiving this because this adviser is assigned to your group.',
            'project' => 'You are receiving this because you are a member of this group.',
            default => 'You are receiving this because this update is visible to your PaperTrail account.',
        };

        $recipients = match ($announcement->audience_type) {
            'global' => User::whereNotNull('email')->get(),
            'adviser_students' => $this->adviserStudentRecipients($announcement->author),
            'project' => $this->projectAnnouncementRecipients($announcement->project),
            default => collect(),
        };

        return $this->sendToUsers(
            $recipients,
            $subject,
            $this->messageHtml(
                $subject,
                ($announcement->author?->name ?? 'PaperTrail') . ' posted an announcement in PaperTrail.',
                $announcement->message,
                $reason
            ),
            $this->notificationFrom()
        );
    }

    public function sendTaskAssigned(Project $project, User $adviser, int $chapter, array $taskTitles): void
    {
        $project->loadMissing('owner', 'members');
        $stageName = $chapter === 0 ? 'Concept Paper' : "Chapter {$chapter}";

        $this->sendToUsers(
            $this->projectAudience($project),
            "PaperTrail: {$stageName} to-do task",
            $this->messageHtml(
                "{$stageName} to-do task",
                "{$adviser->name} assigned task(s) to {$project->title}.",
                implode("\n", array_map(fn ($title) => "- {$title}", $taskTitles)),
                'You are receiving this because you are part of the group assigned to these tasks.'
            ),
            $this->notificationFrom()
        );
    }

    public function sendMeetingScheduled(DefenseSchedule $schedule): int
    {
        $schedule->loadMissing('student', 'adviser', 'project.owner', 'project.members', 'creator');

        $recipients = $this->defenseScheduleRecipients($schedule);
        $creatorName = $schedule->creator?->name ?? 'A PaperTrail user';

        $details = collect([
            "Title: {$schedule->title}",
            $schedule->project?->title ? "Project: {$schedule->project->title}" : null,
            "Starts: {$schedule->start_time?->format('M d, Y h:i A')}",
            "Ends: {$schedule->end_time?->format('M d, Y h:i A')}",
            "Platform: Manual link",
            $schedule->meeting_link ? "Meeting link: {$schedule->meeting_link}" : null,
        ])->filter()->implode("\n");

        return $this->sendToUsers(
            $recipients,
            'PaperTrail: Meeting scheduled',
            $this->messageHtml(
                'Meeting scheduled',
                "{$creatorName} scheduled {$schedule->title}.",
                $details,
                'You are receiving this because you are listed as a participant for this schedule.'
            ),
            $this->notificationFrom()
        );
    }

    public function sendAdviserRequestReceived(AdviserStudent $request): void
    {
        $request->loadMissing('student', 'adviser');

        $this->sendToUsers(
            collect([$request->adviser]),
            'PaperTrail: Adviser request received',
            $this->messageHtml(
                'Adviser request received',
                "{$request->student?->name} sent you an adviser request.",
                $request->message ?: 'No message was included.',
                'You are receiving this because students can send adviser requests to your PaperTrail account.'
            ),
            $this->notificationFrom()
        );
    }

    public function sendUserRegistrationPending(User $user): void
    {
        $this->sendToUsers(
            User::where('role', 'Admin')->whereNotNull('email')->get(),
            'PaperTrail: New user pending verification',
            $this->messageHtml(
                'New user pending verification',
                "{$user->name} submitted a PaperTrail sign-up request.",
                collect([
                    "Name: {$user->name}",
                    "Email: {$user->email}",
                    "Course: {$user->course}",
                    "Section: {$user->section}",
                    "Campus: {$user->campus}",
                ])->filter()->implode("\n"),
                'You are receiving this because admins review new PaperTrail account registrations.'
            ),
            $this->notificationFrom()
        );
    }

    public function sendPasswordResetCode(User $user, string $code): bool
    {
        return $this->sendToUsers(
            collect([$user]),
            'PaperTrail: Password reset code',
            $this->messageHtml(
                'Password reset code',
                'Use this code to reset your PaperTrail password.',
                "Your password reset code is: {$code}",
                'You are receiving this because someone requested a password reset for your PaperTrail account.'
            )
        ) > 0;
    }

    public function sendChatMentionReceived(User $recipient, User $sender, ChatRoom $chatRoom): void
    {
        $this->sendToUsers(
            collect([$recipient]),
            "PaperTrail: {$sender->name} mentioned you",
            $this->messageHtml(
                "{$sender->name} mentioned you",
                "{$sender->name} mentioned you in {$chatRoom->name}.",
                'You can check the message in PaperTrail.',
                'You are receiving this because someone mentioned your PaperTrail account in chat.'
            ),
            $this->notificationFrom()
        );
    }

    public function sendChatMentionDigest(User $recipient, int $mentionCount): void
    {
        $this->sendToUsers(
            collect([$recipient]),
            'PaperTrail: Someone mentioned you',
            $this->messageHtml(
                'Someone mentioned you',
                "Someone mentioned you {$mentionCount} times.",
                'You can check it in PaperTrail.',
                'You are receiving this summary so repeated chat mentions do not send too many emails.'
            ),
            $this->notificationFrom()
        );
    }

    private function sendToUsers(Collection $users, string $subject, array $emailData, ?array $from = null): int
    {
        $emails = $users
            ->filter()
            ->pluck('email')
            ->filter()
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        Log::info('Preparing email notifications.', [
            'subject' => $subject,
            'recipient_count' => $emails->count(),
            'mailer' => config('mail.default'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
            'from' => $from['address'] ?? config('mail.from.address'),
        ]);

        $sentCount = 0;
        foreach ($emails as $email) {
            try {
                if ($this->sendViaBrevoApi($email, $subject, $emailData, $from)) {
                    $sentCount++;

                    continue;
                }

                $this->sendViaLaravelMail($email, $subject, $emailData, $from);
                $sentCount++;
            } catch (\Throwable $exception) {
                Log::warning('Failed to send email notification.', [
                    'email' => $email,
                    'subject' => $subject,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        Log::info('Finished email notifications.', [
            'subject' => $subject,
            'recipient_count' => $emails->count(),
            'sent_count' => $sentCount,
        ]);

        return $sentCount;
    }

    private function sendViaBrevoApi(string $email, string $subject, array $emailData, ?array $from = null): bool
    {
        $apiKey = config('services.brevo.key');

        if (! $apiKey) {
            return false;
        }

        $senderAddress = $from['address'] ?? config('mail.from.address');
        $senderName = $from['name'] ?? config('mail.from.name');
        $htmlBody = view('emails.papertrail-notification', $emailData)->render();
        $textBody = view('emails.papertrail-notification-text', $emailData)->render();

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])->timeout((int) config('mail.mailers.smtp.timeout', 10))
            ->post(config('services.brevo.endpoint'), [
                'sender' => [
                    'name' => $senderName,
                    'email' => $senderAddress,
                ],
                'to' => [
                    ['email' => $email],
                ],
                'subject' => $subject,
                'htmlContent' => $htmlBody,
                'textContent' => $textBody,
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::warning('Brevo API email notification failed.', [
            'email' => $email,
            'subject' => $subject,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ]);

        return false;
    }

    private function sendViaLaravelMail(string $email, string $subject, array $emailData, ?array $from = null): void
    {
        Mail::to($email)->send(new PaperTrailNotification(
            $subject,
            $emailData,
            $from['address'] ?? null,
            $from['name'] ?? null
        ));
    }

    private function adviserStudentRecipients(?User $adviser): Collection
    {
        if (! $adviser) {
            return collect();
        }

        $leaders = AdviserStudent::query()
            ->approved()
            ->active()
            ->where('adviser_id', $adviser->id)
            ->with(['student.ownedProjects.members'])
            ->get()
            ->pluck('student')
            ->filter();

        return $leaders
            ->flatMap(fn (User $leader) => $this->leaderGroupAudience($leader))
            ->unique('id')
            ->values();
    }

    private function projectAudience(?Project $project): Collection
    {
        if (! $project) {
            return collect();
        }

        return collect([$project->owner])
            ->filter()
            ->merge($project->members)
            ->unique('id')
            ->values();
    }

    private function projectAnnouncementRecipients(?Project $project): Collection
    {
        if (! $project) {
            return collect();
        }

        $project->loadMissing('members', 'adviser');

        return collect([$project->adviser])
            ->filter()
            ->merge($project->members)
            ->unique('id')
            ->values();
    }

    private function leaderGroupAudience(User $leader): Collection
    {
        $leader->loadMissing('ownedProjects.members');

        return collect([$leader])
            ->merge($leader->ownedProjects->flatMap->members)
            ->unique('id')
            ->values();
    }

    private function defenseScheduleRecipients(DefenseSchedule $schedule): Collection
    {
        $panelMembers = $schedule->panel_members
            ? User::whereIn('id', $schedule->panel_members)->get()
            : collect();

        return collect([$schedule->student, $schedule->adviser])
            ->filter()
            ->merge($schedule->project ? $this->projectAudience($schedule->project) : collect())
            ->merge($panelMembers)
            ->unique('id')
            ->values();
    }

    private function messageHtml(string $title, string $intro, string $body, string $reason): array
    {
        return compact('title', 'intro', 'body', 'reason');
    }

    private function notificationFrom(): array
    {
        return [
            'address' => config('mail.notifications.address'),
            'name' => config('mail.notifications.name'),
        ];
    }
}
