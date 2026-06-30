<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {

            $table->id('patient_id');

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users','user_id');

            $table->date('date_of_birth')->nullable();

            $table->enum('diabetes_type',[
                'Tipe 1',
                'Tipe 2'
            ])->nullable();

            $table->date('diagnosis_date')->nullable();

            $table->decimal('height_cm',5,2)->nullable();

            $table->foreignId('blood_type_id')
                ->nullable()
                ->constrained('blood_types','blood_type_id');

            $table->foreignId('rhesus_type_id')
                ->nullable()
                ->constrained('rhesus_types','rhesus_type_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
