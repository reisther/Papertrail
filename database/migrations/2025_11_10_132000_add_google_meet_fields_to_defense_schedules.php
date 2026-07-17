<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meeting_schedules', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('meeting_link');
            $table->string('google_calendar_link')->nullable()->after('google_event_id');
            $table->boolean('auto_create_meet')->default(false)->after('google_calendar_link');
            $table->enum('meeting_platform', ['manual', 'google_meet', 'zoom', 'teams'])->default('manual')->after('auto_create_meet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_schedules', function (Blueprint $table) {
            $table->dropColumn(['google_event_id', 'google_calendar_link', 'auto_create_meet', 'meeting_platform']);
        });
    }
};
