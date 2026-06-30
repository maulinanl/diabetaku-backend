<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physiological_records', function (Blueprint $table) {

            $table->id('physiological_id');

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');

            $table->foreignId('input_by_user_id')
                ->constrained('users', 'user_id');

            $table->smallInteger('systolic')->nullable();

            $table->smallInteger('diastolic')->nullable();

            $table->decimal('weight_kg', 5, 2)->nullable();

            $table->timestamp('measured_at');

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
        Schema::dropIfExists('physiological_records');
    }
};
