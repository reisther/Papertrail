<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('login_delay_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('locked_until')->nullable()->after('login_delay_until');
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('token');
            $table->string('purpose', 32)->default('password_reset')->after('attempts');
        });

        Schema::create('login_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('ip_address', 45)->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['user_id', 'fingerprint']);
        });

        Schema::create('admin_account_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('previous_email');
            $table->string('new_email');
            $table->string('verification_channel', 32);
            $table->text('reason');
            $table->string('temporary_document_path')->nullable();
            $table->timestamp('document_delete_after')->nullable();
            $table->timestamp('document_deleted_at')->nullable();
            $table->string('reset_token_hash', 64);
            $table->timestamp('reset_token_expires_at');
            $table->timestamp('reset_token_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_account_recoveries');
        Schema::dropIfExists('login_devices');

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'purpose']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'login_delay_until', 'locked_until']);
        });
    }
};
