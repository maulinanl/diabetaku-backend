<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminMasterDataController extends Controller
{
    private array $masters = [
        'specializations' => [
            'title' => 'Spesialisasi Dokter',
            'table' => 'specializations',
            'primary_key' => 'specialization_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'specialization_name' => 'Nama Spesialisasi',
            ],
        ],
        'activity-types' => [
            'title' => 'Jenis Aktivitas',
            'table' => 'activity_types',
            'primary_key' => 'activity_type_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'activity_name' => 'Nama Aktivitas',
            ],
        ],
        'meal-types' => [
            'title' => 'Jenis Makan',
            'table' => 'meal_types',
            'primary_key' => 'meal_type_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'meal_type_name' => 'Nama Jenis Makan',
            ],
        ],
        'blood-types' => [
            'title' => 'Golongan Darah',
            'table' => 'blood_types',
            'primary_key' => 'blood_type_id',
            'timestamps' => ['created_at'],
            'fields' => [
                'blood_type' => 'Golongan Darah',
            ],
            'options' => [
                'blood_type' => ['A' => 'A', 'B' => 'B', 'AB' => 'AB', 'O' => 'O'],
            ],
        ],
        'rhesus-types' => [
            'title' => 'Rhesus',
            'table' => 'rhesus_types',
            'primary_key' => 'rhesus_type_id',
            'timestamps' => ['created_at'],
            'fields' => [
                'rhesus_type' => 'Jenis Rhesus',
            ],
            'options' => [
                'rhesus_type' => ['+' => 'Positif (+)', '-' => 'Negatif (-)'],
            ],
        ],
        'relation-types' => [
            'title' => 'Tipe Relasi Pendamping',
            'table' => 'relation_types',
            'primary_key' => 'relation_type_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'relation_name' => 'Nama Relasi',
            ],
        ],
        'clinical-parameters' => [
            'title' => 'Parameter Klinis',
            'table' => 'clinical_parameters',
            'primary_key' => 'parameter_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'parameter_name' => 'Nama Parameter',
                'default_min' => 'Nilai Minimum',
                'default_max' => 'Nilai Maksimum',
                'valid_min' => 'Rentang Valid Minimum',
                'valid_max' => 'Rentang Valid Maksimum',
                'unit' => 'Satuan',
            ],
        ],
        'medications' => [
            'title' => 'Data Obat',
            'table' => 'medications',
            'primary_key' => 'medication_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'medication_name' => 'Nama Obat',
                'dosage_form' => 'Bentuk Sediaan',
                'value' => 'Nilai Dosis',
                'unit' => 'Satuan',
                'description' => 'Deskripsi',
                'is_active' => 'Status',
            ],
            'options' => [
                'dosage_form' => [
                    'Tablet' => 'Tablet',
                    'Kapsul' => 'Kapsul',
                    'Sirup' => 'Sirup',
                    'Injeksi' => 'Injeksi',
                    'Tetes' => 'Tetes',
                    'Krim/Salep' => 'Krim/Salep',
                ],
                'is_active' => ['1' => 'Aktif', '0' => 'Tidak Aktif'],
            ],
        ],
        'medication-sessions' => [
            'title' => 'Sesi Minum Obat',
            'table' => 'medication_sessions',
            'primary_key' => 'session_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'session_name' => 'Nama Sesi',
                'start_time' => 'Jam Mulai',
                'end_time' => 'Jam Selesai',
                'default_reminder_time' => 'Jam Pengingat Default',
                'is_active' => 'Status',
            ],
            'options' => [
                'is_active' => ['1' => 'Aktif', '0' => 'Tidak Aktif'],
            ],
        ],
        'notification-types' => [
            'title' => 'Tipe Notifikasi',
            'table' => 'notification_types',
            'primary_key' => 'notification_type_id',
            'timestamps' => ['created_at', 'updated_at'],
            'fields' => [
                'notification_type_name' => 'Nama Tipe Notifikasi',
            ],
        ],
    ];

    private function config(string $type): array
    {
        abort_if(!isset($this->masters[$type]), 404);

        return $this->masters[$type];
    }

    private function rules(string $type, array $config, $ignoreId = null): array
    {
        $unique = function (string $column) use ($config, $ignoreId) {
            $rule = Rule::unique($config['table'], $column);

            if ($ignoreId !== null) {
                $rule->ignore($ignoreId, $config['primary_key']);
            }

            return $rule;
        };

        return match ($type) {
            'specializations' => [
                'specialization_name' => ['required', 'string', 'max:100', $unique('specialization_name')],
            ],
            'activity-types' => [
                'activity_name' => ['required', 'string', 'max:100', $unique('activity_name')],
            ],
            'meal-types' => [
                'meal_type_name' => ['required', 'string', 'max:50', $unique('meal_type_name')],
            ],
            'blood-types' => [
                'blood_type' => ['required', Rule::in(['A', 'B', 'AB', 'O']), $unique('blood_type')],
            ],
            'rhesus-types' => [
                'rhesus_type' => ['required', Rule::in(['+', '-']), $unique('rhesus_type')],
            ],
            'relation-types' => [
                'relation_name' => ['required', 'string', 'max:50', $unique('relation_name')],
            ],
            'clinical-parameters' => [
                'parameter_name' => ['required', 'string', 'max:100', $unique('parameter_name')],
                'default_min' => 'required|numeric',
                'default_max' => 'required|numeric|gt:default_min',
                'valid_min' => 'required|numeric|lte:default_min',
                'valid_max' => 'required|numeric|gte:default_max|gt:valid_min',
                'unit' => 'required|string|max:20',
            ],
            'medications' => [
                'medication_name' => ['required', 'string', 'max:100', $unique('medication_name')],
                'dosage_form' => ['nullable', Rule::in(['Tablet', 'Kapsul', 'Sirup', 'Injeksi', 'Tetes', 'Krim/Salep'])],
                'value' => 'nullable|numeric|min:0',
                'unit' => 'nullable|string|max:20',
                'description' => 'nullable|string',
                'is_active' => 'required|boolean',
            ],
            'medication-sessions' => [
                'session_name' => ['required', 'string', 'max:50', $unique('session_name')],
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'default_reminder_time' => 'nullable|date_format:H:i',
                'is_active' => 'required|boolean',
            ],
            'notification-types' => [
                'notification_type_name' => ['required', 'string', 'max:100', $unique('notification_type_name')],
            ],
        };
    }

    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
            'unique' => ':attribute sudah digunakan.',
            'in' => 'Pilihan :attribute tidak valid.',
            'numeric' => ':attribute harus berupa angka.',
            'min.numeric' => ':attribute minimal :min.',
            'gt.numeric' => ':attribute harus lebih besar dari :value.',
            'gte.numeric' => ':attribute harus lebih besar atau sama dengan :value.',
            'lte.numeric' => ':attribute harus lebih kecil atau sama dengan :value.',
            'date_format' => 'Format :attribute harus jam dan menit.',
            'after' => ':attribute harus setelah :date.',
            'boolean' => 'Pilihan :attribute tidak valid.',
        ];
    }

    private function payload(Request $request, array $config): array
    {
        $payload = [];

        foreach ($config['fields'] as $field => $label) {
            $value = $request->input($field);

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '') {
                $value = null;
            }

            if ($field === 'is_active') {
                $value = $request->boolean($field);
            }

            $payload[$field] = $value;
        }

        return $payload;
    }

    private function constraintState(QueryException $exception): string
    {
        return (string) ($exception->errorInfo[0] ?? $exception->getCode());
    }

    public function index(string $type = 'specializations')
    {
        $config = $this->config($type);

        $items = DB::table($config['table'])
            ->orderBy($config['primary_key'])
            ->get();

        $masterMenus = $this->masters;

        return view('admin.master.index', compact(
            'type',
            'config',
            'items',
            'masterMenus'
        ));
    }

    public function store(Request $request, string $type)
    {
        $config = $this->config($type);

        $request->validate(
            $this->rules($type, $config),
            $this->validationMessages()
        );


        try {

            DB::table($config['table'])->insert(
                $this->payload($request, $config)
            );


            return back()->with(
                'success',
                $config['title'].' berhasil ditambahkan.'
            );


        } catch (QueryException $e) {


            \Log::error($e->getMessage());


            return back()
                ->withInput()
                ->with(
                    'error',
                    'Data gagal ditambahkan.'
                );

        }
    }

    public function update(Request $request, $type, $id)
    {
        $config = $this->config($type);


        $request->validate(
            $this->rules($type, $config, $id),
            $this->validationMessages()
        );


        DB::table($config['table'])
            ->where($config['primary_key'], $id)
            ->update(
                $this->payload($request, $config)
            );


        return redirect()
            ->route('admin.web.master.index', $type)
            ->with(
                'success',
                $config['title'].' berhasil diperbarui.'
            );
    }

    public function destroy(
        string $type,
        $id
    ){

        $config=$this->masters[$type];


        try {

            if (isset($config['fields']['is_active'])) {
                DB::table($config['table'])
                    ->where($config['primary_key'], $id)
                    ->update(['is_active' => false]);

                return back()->with(
                    'success',
                    'Data berhasil dinonaktifkan karena masih digunakan oleh sistem.'
                );
            }

            DB::table($config['table'])
                ->where($config['primary_key'], $id)
                ->delete();

            return back()->with(
                'success',
                'Data berhasil dihapus.'
            );

        } catch(\Exception $e){

            return back()->with(
                'error',
                'Data tidak dapat dihapus karena masih memiliki relasi dengan data lain.'
            );

        }

    }

    public function edit($type, $id)
    {
        $config = $this->config($type);


        $editData = DB::table($config['table'])
            ->where($config['primary_key'], $id)
            ->first();


        abort_if(!$editData, 404);



        $items = DB::table($config['table'])
            ->orderBy($config['primary_key'])
            ->get();



        $masterMenus = $this->masters;



        return view('admin.master.index', compact(
            'type',
            'config',
            'items',
            'editData',
            'masterMenus'
        ));
    }
}
