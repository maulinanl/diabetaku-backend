<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {

            $table->id('prescription_id');

            $table->foreignId('doctor_patient_relation_id')
                ->constrained('doctor_patient_relations', 'doctor_patient_relation_id');

            $table->foreignId('medication_id')
                ->constrained('medications', 'medication_id');

            $table->decimal('quantity', 8, 2)->nullable();

            $table->string('quantity_unit', 50)->nullable();

            $table->enum('meal_rule', [
                'Sebelum Makan',
                'Sesudah Makan',
                'Saat Makan',
                'Sebelum Tidur',
                'Bangun Tidur',
                'Bebas'
            ])->nullable();

            $table->date('start_date')->nullable();

            $table->date('end_date')->nullable();

            $table->enum('status_prescription', [
                'Aktif',
                'Selesai',
                'Diganti',
                'Dihentikan'
            ])->default('Aktif');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('replaced_by')->nullable();

            $table->foreign('replaced_by')
                ->references('prescription_id')
                ->on('prescriptions');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
