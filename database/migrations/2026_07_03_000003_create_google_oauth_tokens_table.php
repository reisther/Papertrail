<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->json('token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_oauth_tokens');
    }
};
