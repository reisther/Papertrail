<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->string('type')->default('meeting')->change();
        });

        DB::table('defense_schedules')
            ->whereIn('type', ['proposal', 'final', 'oral_exam'])
            ->update(['type' => 'meeting']);
    }

    public function down(): void
    {
        DB::table('defense_schedules')
            ->whereIn('type', ['meeting', 'consultation'])
            ->update(['type' => 'final']);

        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->enum('type', ['proposal', 'final', 'oral_exam'])->default('final')->change();
        });
    }
};
