<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_schedules', function (Blueprint $table) {

            $table->id('prescription_schedule_id');

            $table->foreignId('prescription_id')
                ->constrained('prescriptions', 'prescription_id');

            $table->foreignId('session_id')
                ->constrained('medication_sessions', 'session_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_schedules');
    }
};
