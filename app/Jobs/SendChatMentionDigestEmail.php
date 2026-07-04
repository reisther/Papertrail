<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendChatMentionDigestEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $userId)
    {
    }

    public function handle(EmailNotificationService $emailNotificationService): void
    {
        $cacheKey = "chat_mention_email_window:{$this->userId}";
        $window = Cache::get($cacheKey);
        $mentionCount = (int) ($window['count'] ?? 0);

        if ($mentionCount <= 1) {
            return;
        }

        $recipient = User::find($this->userId);

        if (! $recipient) {
            Log::warning('Skipped chat mention digest because the recipient no longer exists.', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $emailNotificationService->sendChatMentionDigest($recipient, $mentionCount);
        Cache::forget($cacheKey);
    }
}
