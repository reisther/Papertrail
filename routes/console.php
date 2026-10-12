<?php

use App\Models\AdminAccountRecovery;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('accounts:purge-deleted', function (AccountDeletionService $deletions): void {
    $this->info($deletions->purgeDueAccounts().' account(s) anonymized; shared history retained.');
})->purpose('Remove account access and personal data after the 30-day recovery period');

Schedule::command('accounts:purge-deleted')->everyMinute()->withoutOverlapping();

Schedule::call(function (): void {
    AdminAccountRecovery::query()
        ->whereNotNull('temporary_document_path')
        ->whereNull('document_deleted_at')
        ->where('document_delete_after', '<=', now())
        ->chunkById(100, function ($recoveries): void {
            foreach ($recoveries as $recovery) {
                Storage::disk('local')->delete($recovery->temporary_document_path);
                $recovery->update(['document_deleted_at' => now()]);
            }
        });
})->dailyAt('02:00')->name('purge-account-recovery-documents')->withoutOverlapping();
