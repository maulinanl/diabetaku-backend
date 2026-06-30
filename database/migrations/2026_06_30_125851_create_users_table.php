<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');

            $table->foreignId('role_id')
                ->constrained('roles', 'role_id');

            $table->string('email',150)->unique();

            $table->string('password_hash',255);

            $table->string('full_name',150);

            $table->string('phone_number',20)->nullable();

            $table->enum('gender',[
                'Laki-laki',
                'Perempuan'
            ])->nullable();

            $table->enum('account_status',[
                'Menunggu Verifikasi',
                'Aktif',
                'Tidak Aktif',
                'Terkunci'
            ])->default('Menunggu Verifikasi');

            $table->smallInteger('login_attempts')->default(0);

            $table->timestamp('locked_until')->nullable();

            $table->timestamp('email_verified_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
