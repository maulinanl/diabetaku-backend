<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_notes', function (Blueprint $table) {

            $table->id('clinical_note_id');

            $table->foreignId('doctor_patient_relation_id')
                ->constrained('doctor_patient_relations', 'doctor_patient_relation_id');

            $table->text('patient_condition')->nullable();

            $table->text('doctor_note')->nullable();

            $table->text('treatment_plan')->nullable();

            $table->date('follow_up_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_notes');
    }
};
