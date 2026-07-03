<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\EmailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAnnouncementEmailNotifications implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $announcementId)
    {
    }

    public function handle(EmailNotificationService $emailNotificationService): void
    {
        $announcement = Announcement::find($this->announcementId);

        if (! $announcement) {
            Log::warning('Skipped announcement email job because the announcement no longer exists.', [
                'announcement_id' => $this->announcementId,
            ]);

            return;
        }

        $emailNotificationService->sendAnnouncementPosted($announcement);
    }
}
