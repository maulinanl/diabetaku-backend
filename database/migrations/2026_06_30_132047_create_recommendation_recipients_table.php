<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_recipients', function (Blueprint $table) {

            $table->foreignId('recommendation_id')
                ->constrained('recommendations', 'recommendation_id')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users', 'user_id');

            $table->boolean('is_read')->default(false);

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->primary([
                'recommendation_id',
                'user_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_recipients');
    }
};
