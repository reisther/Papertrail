<?php

namespace App\Services;

use App\Models\AdviserStudent;
use App\Models\Announcement;
use App\Models\DefenseSchedule;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public function sendAnnouncementPosted(Announcement $announcement): void
    {
        $announcement->loadMissing('author', 'project.owner', 'project.members');

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
            'project' => $this->projectMemberRecipients($announcement->project),
            default => collect(),
        };

        $this->sendToUsers(
            $recipients,
            $subject,
            $this->messageHtml(
                $subject,
                ($announcement->author?->name ?? 'PaperTrail') . ' posted an announcement in PaperTrail.',
                $announcement->message,
                $reason
            )
        );
    }

    public function sendTaskAssigned(Project $project, User $adviser, int $chapter, array $taskTitles): void
    {
        $project->loadMissing('owner', 'members');

        $this->sendToUsers(
            $this->projectAudience($project),
            "PaperTrail: Chapter {$chapter} to-do task",
            $this->messageHtml(
                "Chapter {$chapter} to-do task",
                "{$adviser->name} assigned task(s) to {$project->title}.",
                implode("\n", array_map(fn ($title) => "- {$title}", $taskTitles)),
                'You are receiving this because you are part of the group assigned to these tasks.'
            )
        );
    }

    public function sendGoogleMeetCreated(DefenseSchedule $schedule): void
    {
        $schedule->loadMissing('student', 'adviser', 'project.owner', 'project.members', 'creator');

        $recipients = $this->defenseScheduleRecipients($schedule);
        $creatorName = $schedule->creator?->name ?? 'A PaperTrail user';

        $details = collect([
            "Title: {$schedule->title}",
            "Starts: {$schedule->start_time?->format('M d, Y h:i A')}",
            "Ends: {$schedule->end_time?->format('M d, Y h:i A')}",
            $schedule->meeting_link ? "Google Meet: {$schedule->meeting_link}" : null,
            $schedule->google_calendar_link ? "Calendar: {$schedule->google_calendar_link}" : null,
        ])->filter()->implode("\n");

        $this->sendToUsers(
            $recipients,
            'PaperTrail: Google Meet created',
            $this->messageHtml(
                'Google Meet created',
                "{$creatorName} created a Google Meet for {$schedule->title}.",
                $details,
                'You are receiving this because you are listed as a participant for this schedule.'
            )
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
            )
        );
    }

    private function sendToUsers(Collection $users, string $subject, array $emailData): void
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
            'from' => config('mail.from.address'),
        ]);

        $sentCount = 0;
        foreach ($emails as $email) {
            try {
                Mail::send(['html' => 'emails.papertrail-notification', 'text' => 'emails.papertrail-notification-text'], $emailData, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
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

    private function projectMemberRecipients(?Project $project): Collection
    {
        if (! $project) {
            return collect();
        }

        return $project->members
            ->filter()
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
}
