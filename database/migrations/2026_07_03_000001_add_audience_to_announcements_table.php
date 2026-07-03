<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('audience_type')->default('global')->after('user_id');
            $table->foreignId('project_id')->nullable()->after('audience_type')->constrained()->nullOnDelete();

            $table->index(['audience_type', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['audience_type', 'project_id']);
            $table->dropColumn(['audience_type', 'project_id']);
        });
    }
};
