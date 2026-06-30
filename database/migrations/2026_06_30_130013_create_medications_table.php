<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id('medication_id');
            $table->string('medication_name', 100)->unique();

            $table->enum('dosage_form', [
                'Tablet',
                'Kapsul',
                'Sirup',
                'Injeksi',
                'Tetes',
                'Krim/Salep',
            ])->nullable();
            
            $table->decimal('value', 8, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
