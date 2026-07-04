<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPasswordResetCodeEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $userId, public string $code)
    {
    }

    public function handle(EmailNotificationService $emailNotificationService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('Skipped password reset code email because the user no longer exists.', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $emailNotificationService->sendPasswordResetCode($user, $this->code);
    }
}
