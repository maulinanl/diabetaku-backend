<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('doctors', 'rejection_reason')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('doctors', 'rejection_reason')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('verified_at');
            });
        }
    }
};
