<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_custom_thresholds', function (Blueprint $table) {

            $table->id('patient_custom_threshold_id');

            $table->foreignId('doctor_patient_relation_id')
                ->constrained('doctor_patient_relations', 'doctor_patient_relation_id');

            $table->foreignId('parameter_id')
                ->constrained('clinical_parameters', 'parameter_id');

            $table->decimal('custom_min', 8, 2)->nullable();

            $table->decimal('custom_max', 8, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['doctor_patient_relation_id', 'parameter_id'],
                'patient_custom_thresholds_relation_parameter_unique'
            );

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_custom_thresholds');
    }
};
