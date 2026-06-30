<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DiabetakuInitialAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();

            /*
            |--------------------------------------------------------------------------
            | Roles
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['role_id' => 1, 'role_name' => 'Admin'],
                ['role_id' => 2, 'role_name' => 'Dokter'],
                ['role_id' => 3, 'role_name' => 'Pasien'],
                ['role_id' => 4, 'role_name' => 'Pendamping'],
            ] as $role) {
                DB::table('roles')->updateOrInsert(
                    ['role_id' => $role['role_id']],
                    [
                        'role_name' => $role['role_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Specializations - 3 data
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['specialization_id' => 1, 'specialization_name' => 'Dokter Umum'],
                ['specialization_id' => 2, 'specialization_name' => 'Penyakit Dalam'],
                ['specialization_id' => 3, 'specialization_name' => 'Endokrinologi'],
            ] as $specialization) {
                DB::table('specializations')->updateOrInsert(
                    ['specialization_id' => $specialization['specialization_id']],
                    [
                        'specialization_name' => $specialization['specialization_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Blood Types
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['blood_type_id' => 1, 'blood_type' => 'A'],
                ['blood_type_id' => 2, 'blood_type' => 'B'],
                ['blood_type_id' => 3, 'blood_type' => 'AB'],
                ['blood_type_id' => 4, 'blood_type' => 'O'],
            ] as $bloodType) {
                DB::table('blood_types')->updateOrInsert(
                    ['blood_type_id' => $bloodType['blood_type_id']],
                    [
                        'blood_type' => $bloodType['blood_type'],
                        'created_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Rhesus Types
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['rhesus_type_id' => 1, 'rhesus_type' => '+'],
                ['rhesus_type_id' => 2, 'rhesus_type' => '-'],
            ] as $rhesusType) {
                DB::table('rhesus_types')->updateOrInsert(
                    ['rhesus_type_id' => $rhesusType['rhesus_type_id']],
                    [
                        'rhesus_type' => $rhesusType['rhesus_type'],
                        'created_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Relation Types
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['relation_type_id' => 1, 'relation_name' => 'Ayah'],
                ['relation_type_id' => 2, 'relation_name' => 'Ibu'],
                ['relation_type_id' => 3, 'relation_name' => 'Suami/Istri'],
                ['relation_type_id' => 4, 'relation_name' => 'Anak'],
                ['relation_type_id' => 5, 'relation_name' => 'Saudara'],
                ['relation_type_id' => 6, 'relation_name' => 'Wali'],
            ] as $relationType) {
                DB::table('relation_types')->updateOrInsert(
                    ['relation_type_id' => $relationType['relation_type_id']],
                    [
                        'relation_name' => $relationType['relation_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Activity Types
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['activity_type_id' => 1, 'activity_name' => 'Jalan Kaki'],
                ['activity_type_id' => 2, 'activity_name' => 'Lari Ringan'],
                ['activity_type_id' => 3, 'activity_name' => 'Bersepeda'],
                ['activity_type_id' => 4, 'activity_name' => 'Senam'],
                ['activity_type_id' => 5, 'activity_name' => 'Yoga'],
                ['activity_type_id' => 6, 'activity_name' => 'Naik Turun Tangga'],
                ['activity_type_id' => 7, 'activity_name' => 'Olahraga Lainnya'],
            ] as $activityType) {
                DB::table('activity_types')->updateOrInsert(
                    ['activity_type_id' => $activityType['activity_type_id']],
                    [
                        'activity_name' => $activityType['activity_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Meal Types
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['meal_type_id' => 1, 'meal_type_name' => 'Sarapan'],
                ['meal_type_id' => 2, 'meal_type_name' => 'Makan Siang'],
                ['meal_type_id' => 3, 'meal_type_name' => 'Makan Malam'],
                ['meal_type_id' => 4, 'meal_type_name' => 'Camilan'],
            ] as $mealType) {
                DB::table('meal_types')->updateOrInsert(
                    ['meal_type_id' => $mealType['meal_type_id']],
                    [
                        'meal_type_name' => $mealType['meal_type_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Medication Sessions
            |--------------------------------------------------------------------------
            */
            foreach ([
                [
                    'session_id' => 1,
                    'session_name' => 'Pagi',
                    'start_time' => '06:00:00',
                    'end_time' => '10:00:00',
                    'default_reminder_time' => '07:00:00',
                ],
                [
                    'session_id' => 2,
                    'session_name' => 'Siang',
                    'start_time' => '11:00:00',
                    'end_time' => '14:00:00',
                    'default_reminder_time' => '12:00:00',
                ],
                [
                    'session_id' => 3,
                    'session_name' => 'Sore',
                    'start_time' => '15:00:00',
                    'end_time' => '17:30:00',
                    'default_reminder_time' => '16:00:00',
                ],
                [
                    'session_id' => 4,
                    'session_name' => 'Malam',
                    'start_time' => '18:00:00',
                    'end_time' => '22:00:00',
                    'default_reminder_time' => '20:00:00',
                ],
                [
                    'session_id' => 5,
                    'session_name' => 'Sebelum Tidur',
                    'start_time' => '21:00:00',
                    'end_time' => '23:59:00',
                    'default_reminder_time' => '22:00:00',
                ],
            ] as $session) {
                DB::table('medication_sessions')->updateOrInsert(
                    ['session_id' => $session['session_id']],
                    [
                        'session_name' => $session['session_name'],
                        'start_time' => $session['start_time'],
                        'end_time' => $session['end_time'],
                        'default_reminder_time' => $session['default_reminder_time'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Medications
            |--------------------------------------------------------------------------
            */
            foreach ([
                [
                    'medication_id' => 1,
                    'medication_name' => 'Metformin',
                    'dosage_form' => 'Tablet',
                    'value' => 500,
                    'unit' => 'mg',
                    'description' => 'Obat antidiabetes oral yang umum digunakan untuk membantu mengontrol kadar gula darah.',
                ],
                [
                    'medication_id' => 2,
                    'medication_name' => 'Glimepiride',
                    'dosage_form' => 'Tablet',
                    'value' => 2,
                    'unit' => 'mg',
                    'description' => 'Obat antidiabetes oral golongan sulfonilurea.',
                ],
                [
                    'medication_id' => 3,
                    'medication_name' => 'Acarbose',
                    'dosage_form' => 'Tablet',
                    'value' => 50,
                    'unit' => 'mg',
                    'description' => 'Obat untuk membantu mengontrol kenaikan gula darah setelah makan.',
                ],
                [
                    'medication_id' => 4,
                    'medication_name' => 'Insulin Rapid Acting',
                    'dosage_form' => 'Injeksi',
                    'value' => 100,
                    'unit' => 'IU/ml',
                    'description' => 'Insulin kerja cepat untuk membantu mengontrol gula darah sekitar waktu makan.',
                ],
                [
                    'medication_id' => 5,
                    'medication_name' => 'Insulin Long Acting',
                    'dosage_form' => 'Injeksi',
                    'value' => 100,
                    'unit' => 'IU/ml',
                    'description' => 'Insulin kerja panjang untuk membantu menjaga gula darah basal.',
                ],
                [
                    'medication_id' => 6,
                    'medication_name' => 'Sitagliptin',
                    'dosage_form' => 'Tablet',
                    'value' => 100,
                    'unit' => 'mg',
                    'description' => 'Obat antidiabetes oral golongan DPP-4 inhibitor.',
                ],
            ] as $medication) {
                DB::table('medications')->updateOrInsert(
                    ['medication_id' => $medication['medication_id']],
                    [
                        'medication_name' => $medication['medication_name'],
                        'dosage_form' => $medication['dosage_form'],
                        'value' => $medication['value'],
                        'unit' => $medication['unit'],
                        'description' => $medication['description'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Clinical Parameters
            |--------------------------------------------------------------------------
            */
            foreach ([
                [
                    'parameter_id' => 1,
                    'parameter_name' => 'Glukosa Puasa',
                    'default_min' => 70,
                    'default_max' => 130,
                    'valid_min' => 20,
                    'valid_max' => 600,
                    'unit' => 'mg/dL',
                ],
                [
                    'parameter_id' => 2,
                    'parameter_name' => 'Glukosa 2 Jam Setelah Makan',
                    'default_min' => 70,
                    'default_max' => 180,
                    'valid_min' => 20,
                    'valid_max' => 600,
                    'unit' => 'mg/dL',
                ],
                [
                    'parameter_id' => 3,
                    'parameter_name' => 'Glukosa Sewaktu',
                    'default_min' => 70,
                    'default_max' => 200,
                    'valid_min' => 20,
                    'valid_max' => 600,
                    'unit' => 'mg/dL',
                ],
                [
                    'parameter_id' => 4,
                    'parameter_name' => 'Tekanan Darah Sistolik',
                    'default_min' => 90,
                    'default_max' => 140,
                    'valid_min' => 50,
                    'valid_max' => 250,
                    'unit' => 'mmHg',
                ],
                [
                    'parameter_id' => 5,
                    'parameter_name' => 'Tekanan Darah Diastolik',
                    'default_min' => 60,
                    'default_max' => 90,
                    'valid_min' => 30,
                    'valid_max' => 150,
                    'unit' => 'mmHg',
                ],
                [
                    'parameter_id' => 9,
                    'parameter_name' => 'BMI',
                    'default_min' => 18.5,
                    'default_max' => 24.9,
                    'valid_min' => 10,
                    'valid_max' => 80,
                    'unit' => 'kg/m²',
                ],
            ] as $parameter) {
                DB::table('clinical_parameters')->updateOrInsert(
                    ['parameter_id' => $parameter['parameter_id']],
                    [
                        'parameter_name' => $parameter['parameter_name'],
                        'default_min' => $parameter['default_min'],
                        'default_max' => $parameter['default_max'],
                        'valid_min' => $parameter['valid_min'],
                        'valid_max' => $parameter['valid_max'],
                        'unit' => $parameter['unit'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Notification Types
            |--------------------------------------------------------------------------
            */
            foreach ([
                ['notification_type_id' => 1, 'notification_type_name' => 'Data Abnormal'],
                ['notification_type_id' => 2, 'notification_type_name' => 'Permintaan Koneksi'],
                ['notification_type_id' => 3, 'notification_type_name' => 'Rekomendasi Dokter'],
                ['notification_type_id' => 4, 'notification_type_name' => 'Pengingat Obat'],
                ['notification_type_id' => 5, 'notification_type_name' => 'Validasi Data'],
                ['notification_type_id' => 6, 'notification_type_name' => 'Putus Relasi'],
                ['notification_type_id' => 7, 'notification_type_name' => 'Resep Obat'],
            ] as $notificationType) {
                DB::table('notification_types')->updateOrInsert(
                    ['notification_type_id' => $notificationType['notification_type_id']],
                    [
                        'notification_type_name' => $notificationType['notification_type_name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Demo Users
            |--------------------------------------------------------------------------
            */
            DB::table('users')->updateOrInsert(
                ['email' => 'admin@diabetaku.com'],
                [
                    'role_id' => 1,
                    'full_name' => 'Admin DiabetaKu',
                    'phone_number' => '080000000001',
                    'gender' => 'Perempuan',
                    'password_hash' => Hash::make('admin123'),
                    'account_status' => 'Aktif',
                    'login_attempts' => 0,
                    'locked_until' => null,
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $adminUserId = DB::table('users')->where('email', 'admin@diabetaku.com')->value('user_id');

            DB::table('admins')->updateOrInsert(
                ['user_id' => $adminUserId],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        });
    }

    private function syncSequences(array $tables): void
    {
        foreach ($tables as $table => $primaryKey) {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', '{$primaryKey}'), COALESCE((SELECT MAX({$primaryKey}) FROM {$table}), 1))");
        }
    }
}
