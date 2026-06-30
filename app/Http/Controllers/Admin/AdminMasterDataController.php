<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminMasterDataController extends Controller
{
    private array $masters = [
        'specializations' => [
            'title' => 'Spesialisasi Dokter',
            'table' => 'specializations',
            'id' => 'specialization_id',
            'columns' => [
                'specialization_name' => 'Nama Spesialisasi',
            ],
        ],
        'activity-types' => [
            'title' => 'Jenis Aktivitas',
            'table' => 'activity_types',
            'id' => 'activity_type_id',
            'columns' => [
                'activity_name' => 'Nama Aktivitas',
            ],
        ],
        'meal-types' => [
            'title' => 'Jenis Makan',
            'table' => 'meal_types',
            'id' => 'meal_type_id',
            'columns' => [
                'meal_type_name' => 'Jenis Makan',
            ],
        ],
        'blood-types' => [
            'title' => 'Golongan Darah',
            'table' => 'blood_types',
            'id' => 'blood_type_id',
            'columns' => [
                'blood_type' => 'Golongan Darah',
            ],
        ],
        'rhesus-types' => [
            'title' => 'Rhesus',
            'table' => 'rhesus_types',
            'id' => 'rhesus_type_id',
            'columns' => [
                'rhesus_type' => 'Rhesus',
            ],
        ],
        'relation-types' => [
            'title' => 'Hubungan Pendamping',
            'table' => 'relation_types',
            'id' => 'relation_type_id',
            'columns' => [
                'relation_name' => 'Nama Hubungan',
            ],
        ],
        'clinical-parameters' => [
            'title' => 'Parameter Klinis',
            'table' => 'clinical_parameters',
            'id' => 'parameter_id',
            'columns' => [
                'parameter_name' => 'Nama Parameter',
                'default_min' => 'Batas Normal Minimum',
                'default_max' => 'Batas Normal Maksimum',
                'valid_min' => 'Rentang Valid Minimum',
                'valid_max' => 'Rentang Valid Maksimum',
                'unit' => 'Satuan',
            ],
        ],
    ];

    private function config(string $type): array
    {
        abort_if(!isset($this->masters[$type]), 404);
        return $this->masters[$type];
    }

    private function rules(string $type, array $config): array
    {
        if ($type === 'clinical-parameters') {
            return [
                'parameter_name' => 'required|string|max:255',
                'default_min' => 'required|numeric',
                'default_max' => 'required|numeric|gt:default_min',
                'valid_min' => 'required|numeric|lte:default_min',
                'valid_max' => 'required|numeric|gte:default_max|gt:valid_min',
                'unit' => 'required|string|max:50',
            ];
        }

        $rules = [];

        foreach ($config['columns'] as $column => $label) {
            $rules[$column] = Str::contains($column, ['min', 'max'])
                ? 'nullable|numeric'
                : 'required|string|max:255';
        }

        return $rules;
    }

    public function index(string $type)
    {
        $config = $this->config($type);

        $items = DB::table($config['table'])
            ->orderBy($config['id'])
            ->get();

        return view('admin.master.index', compact('type', 'config', 'items'));
    }

    public function store(Request $request, string $type)
    {
        $config = $this->config($type);

        $data = $request->validate($this->rules($type, $config));
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table($config['table'])->insert($data);

        return back()->with('success', 'Data master berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $config = $this->config($type);

        $data = $request->validate($this->rules($type, $config));
        $data['updated_at'] = now();

        DB::table($config['table'])
            ->where($config['id'], $id)
            ->update($data);

        return back()->with('success', 'Data master berhasil diperbarui.');
    }

    public function destroy(string $type, int $id)
    {
        $config = $this->config($type);

        DB::table($config['table'])
            ->where($config['id'], $id)
            ->delete();

        return back()->with('success', 'Data master berhasil dihapus.');
    }
}
