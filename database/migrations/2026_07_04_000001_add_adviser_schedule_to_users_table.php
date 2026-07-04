<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('adviser_schedule_path')->nullable()->after('profile_picture_path');
            $table->string('adviser_schedule_name')->nullable()->after('adviser_schedule_path');
            $table->string('adviser_schedule_mime')->nullable()->after('adviser_schedule_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'adviser_schedule_path',
                'adviser_schedule_name',
                'adviser_schedule_mime',
            ]);
        });
    }
};
