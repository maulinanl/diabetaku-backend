<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_patient_relations', function (Blueprint $table) {

            $table->id('doctor_patient_relation_id');

            $table->foreignId('doctor_id')
                ->constrained('doctors', 'doctor_id');

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');

            $table->enum('status', [
                'Menunggu',
                'Diterima',
                'Ditolak',
                'Diputus'
            ])->default('Menunggu');

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();

            $table->timestamps();

            $table->unique(['doctor_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_patient_relations');
    }
};
