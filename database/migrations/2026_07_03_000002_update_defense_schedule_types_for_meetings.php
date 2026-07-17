<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE meeting_schedules DROP CONSTRAINT IF EXISTS meeting_schedules_type_check');
            DB::statement("ALTER TABLE meeting_schedules ALTER COLUMN type SET DEFAULT 'meeting'");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE meeting_schedules MODIFY type VARCHAR(255) NOT NULL DEFAULT 'meeting'");
        }

        DB::table('meeting_schedules')
            ->whereIn('type', ['proposal', 'final', 'oral_exam'])
            ->update(['type' => 'meeting']);
    }

    public function down(): void
    {
        DB::table('meeting_schedules')
            ->whereIn('type', ['meeting', 'consultation'])
            ->update(['type' => 'final']);
    }
};
