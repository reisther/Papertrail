<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('section')->nullable()->change();
        });

        DB::table('users')->where('role', 'Teacher')->update([
            'course' => 'CCS Department',
            'section' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('users')->whereNull('section')->update(['section' => '']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('section')->nullable(false)->change();
        });
    }
};
