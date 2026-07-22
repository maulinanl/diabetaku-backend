<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE prescription_schedules
            ADD CONSTRAINT prescription_schedules_prescription_session_unique
            UNIQUE (prescription_id, session_id)
        ');

        DB::statement('
            ALTER TABLE medication_consumption_logs
            ALTER COLUMN prescription_schedule_id SET NOT NULL
        ');

        DB::statement('
            ALTER TABLE medication_consumption_logs
            ADD CONSTRAINT medication_consumption_logs_schedule_date_unique
            UNIQUE (prescription_schedule_id, log_date)
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE medication_consumption_logs
            DROP CONSTRAINT IF EXISTS medication_consumption_logs_schedule_date_unique
        ');

        DB::statement('
            ALTER TABLE medication_consumption_logs
            ALTER COLUMN prescription_schedule_id DROP NOT NULL
        ');

        DB::statement('
            ALTER TABLE prescription_schedules
            DROP CONSTRAINT IF EXISTS prescription_schedules_prescription_session_unique
        ');
    }
};
