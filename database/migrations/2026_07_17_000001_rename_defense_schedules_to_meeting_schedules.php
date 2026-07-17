<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('defense_schedules') && ! Schema::hasTable('meeting_schedules')) {
            Schema::rename('defense_schedules', 'meeting_schedules');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('meeting_schedules') && ! Schema::hasTable('defense_schedules')) {
            Schema::rename('meeting_schedules', 'defense_schedules');
        }
    }
};
