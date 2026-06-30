<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_records', function (Blueprint $table) {

            $table->id('meal_id');

            $table->foreignId('patient_id')
                ->constrained('patients', 'patient_id');

            $table->foreignId('input_by_user_id')
                ->constrained('users', 'user_id');

            $table->foreignId('meal_type_id')
                ->nullable()
                ->constrained('meal_types', 'meal_type_id');

            $table->text('food_description')->nullable();

            $table->decimal('carbohydrate_estimate', 6, 2)->nullable();

            $table->decimal('calories', 8, 2)->nullable();

            $table->date('meal_date');

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
        Schema::dropIfExists('meal_records');
    }
};
