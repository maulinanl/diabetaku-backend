<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caregiver_patient_relations', function (Blueprint $table) {

            $table->id('caregiver_patient_relation_id');

            $table->foreignId('caregiver_id')
                ->constrained('caregivers', 'caregiver_id');

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');

            $table->foreignId('relation_type_id')
                ->nullable()
                ->constrained('relation_types', 'relation_type_id');

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

            $table->unique(['caregiver_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregiver_patient_relations');
    }
};
