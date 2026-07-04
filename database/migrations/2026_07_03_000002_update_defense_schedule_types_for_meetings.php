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
            DB::statement('ALTER TABLE defense_schedules DROP CONSTRAINT IF EXISTS defense_schedules_type_check');
            DB::statement("ALTER TABLE defense_schedules ALTER COLUMN type SET DEFAULT 'meeting'");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE defense_schedules MODIFY type VARCHAR(255) NOT NULL DEFAULT 'meeting'");
        }

        DB::table('defense_schedules')
            ->whereIn('type', ['proposal', 'final', 'oral_exam'])
            ->update(['type' => 'meeting']);
    }

    public function down(): void
    {
        DB::table('defense_schedules')
            ->whereIn('type', ['meeting', 'consultation'])
            ->update(['type' => 'final']);
    }
};
