<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_consumption_logs', function (Blueprint $table) {

            $table->id('log_id');

            $table->foreignId('prescription_schedule_id')
                ->nullable()
                ->constrained('prescription_schedules', 'prescription_schedule_id');

            $table->foreignId('input_by_user_id')
                ->constrained('users', 'user_id');

            $table->date('log_date');

            $table->enum('status', [
                'Diminum',
                'Terlewat',
                'Dibatalkan'
            ])->default('Terlewat');

            $table->timestamp('taken_at')->nullable();

            $table->text('note')->nullable();

            $table->enum('validation_status', [
                'Menunggu',
                'Valid',
                'Tidak Valid'
            ])->default('Menunggu');

            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_consumption_logs');
    }
};
