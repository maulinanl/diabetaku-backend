<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {

            $table->id('doctor_id');

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users','user_id');

            $table->foreignId('specialization_id')
                ->nullable()
                ->constrained('specializations','specialization_id');

            $table->string('str_number',50)->unique();

            $table->string('institution',200)->nullable();

            $table->enum('verification_status',[
                'Menunggu',
                'Disetujui',
                'Ditolak'
            ])->default('Menunggu');

            $table->foreignId('verified_by_admin_id')
                ->nullable()
                ->constrained('admins','admin_id');

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
