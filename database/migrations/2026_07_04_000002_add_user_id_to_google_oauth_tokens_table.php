<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_oauth_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dropUnique(['provider']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::table('google_oauth_tokens', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('provider');
        });
    }
};
