<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_records', function (Blueprint $table) {

            $table->id('activity_id');

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');

            $table->foreignId('input_by_user_id')
                ->constrained('users', 'user_id');

            $table->foreignId('activity_type_id')
                ->nullable()
                ->constrained('activity_types', 'activity_type_id');

            $table->smallInteger('duration_minutes')->nullable();

            $table->enum('intensity', [
                'Ringan',
                'Sedang',
                'Berat'
            ])->nullable();

            $table->date('activity_date');

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
        Schema::dropIfExists('activity_records');
    }
};
