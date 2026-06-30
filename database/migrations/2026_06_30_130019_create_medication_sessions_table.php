<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->string('session_name', 50)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->time('default_reminder_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_sessions');
    }
};
