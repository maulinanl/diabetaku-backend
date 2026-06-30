<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {

            $table->id('recommendation_id');

            $table->foreignId('clinical_note_id')
                ->constrained('clinical_notes', 'clinical_note_id');

            $table->enum('category', [
                'Obat',
                'Pola Makan',
                'Gaya Hidup'
            ]);

            $table->text('recommendation_text');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
