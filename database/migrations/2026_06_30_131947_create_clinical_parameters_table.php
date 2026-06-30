<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_parameters', function (Blueprint $table) {
            $table->id('parameter_id');
            $table->string('parameter_name', 100)->unique();
            $table->decimal('default_min', 8, 2)->nullable();
            $table->decimal('default_max', 8, 2)->nullable();
            $table->decimal('valid_min', 10, 2)->nullable();
            $table->decimal('valid_max', 10, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_parameters');
    }
};
