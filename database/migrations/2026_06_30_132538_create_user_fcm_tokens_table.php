<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_fcm_tokens', function (Blueprint $table) {

            $table->id('user_fcm_token_id');

            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->cascadeOnDelete();

            $table->text('fcm_token');

            $table->string('device_id')->nullable();

            $table->string('platform', 50)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamp('logged_out_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'fcm_token']);

            $table->index(['user_id', 'is_active'], 'idx_user_fcm_tokens_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fcm_tokens');
    }
};
