@extends('admin.layouts.app')

@section('title', 'Data Master')
@section('subtitle', 'Kelola data referensi yang digunakan oleh fitur aplikasi diabetAku.')

@section('content')
    @php
        $oldFormContext = old('_form_context');
        $nullableFields = ['dosage_form', 'value', 'unit', 'description', 'default_reminder_time'];
    @endphp

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Kategori Master Data</h3>
                <p class="card-desc">Pilih kategori data master yang ingin ditambah atau diperbarui.</p>
            </div>
        </div>

        <div class="menu-pills">
            @foreach ($masterMenus as $key => $menu)
                <a href="{{ route('admin.web.master.index', $key) }}"
                    class="btn {{ $type === $key ? 'btn-primary' : 'btn-outline' }}">
                    {{ $menu['title'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Tambah {{ $config['title'] }}</h3>
                <p class="card-desc">Isi data baru, lalu simpan agar dapat digunakan pada aplikasi.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.web.master.store', $type) }}">
            @csrf
            <input type="hidden" name="_form_context" value="create">

            <div class="form-grid">
                @foreach ($config['fields'] as $field => $label)
                    @php
                        $isNumeric = str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value';
                        $isTime = str_contains($field, 'time');
                        $isRequired = !in_array($field, $nullableFields, true);
                        $createValue = $oldFormContext === 'create' ? old($field) : ($field === 'is_active' ? '1' : '');
                    @endphp

                    <div class="form-group">
                        <label for="create_{{ $field }}">{{ $label }}</label>

                        @if (isset($config['options'][$field]))
                            <select id="create_{{ $field }}" name="{{ $field }}" class="form-control"
                                @required($isRequired)>
                                @if ($field !== 'is_active')
                                    <option value="">Pilih {{ strtolower($label) }}</option>
                                @endif

                                @foreach ($config['options'][$field] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $createValue === (string) $optionValue)>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif($field === 'description')
                            <textarea id="create_{{ $field }}" name="{{ $field }}" class="form-control" rows="3"
                                placeholder="Masukkan {{ strtolower($label) }}">{{ $createValue }}</textarea>
                        @else
                            <input id="create_{{ $field }}"
                                type="{{ $isTime ? 'time' : ($isNumeric ? 'number' : 'text') }}"
                                @if ($isNumeric) step="0.01" @endif name="{{ $field }}"
                                value="{{ $createValue }}" class="form-control"
                                placeholder="Masukkan {{ strtolower($label) }}" @required($isRequired)>
                        @endif
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;">
                Tambah Data
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar {{ $config['title'] }}</h3>
                <<p class="card-desc"> Daftar data yang tersedia pada sistem. </p>
            </div>
            <span class="badge badge-blue">{{ $items->count() }} data</span>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>

                        @foreach ($config['fields'] as $field => $label)
                            <th>{{ $label }}</th>
                        @endforeach

                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($items as $item)
                        @php
                            $primaryKey = $config['primary_key'];
                            $itemId = data_get($item, $primaryKey);
                            $rowContext = 'update-' . $itemId;
                            $useOldRowValues = $oldFormContext === $rowContext;
                            $updateFormId = 'update-master-' . $type . '-' . $itemId;
                        @endphp

                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            @foreach ($config['fields'] as $field => $label)
                                @php
                                    $storedValue = data_get($item, $field, '');
                                    $rowValue = $useOldRowValues ? old($field) : $storedValue;
                                    $isNumeric =
                                        str_contains($field, 'min') ||
                                        str_contains($field, 'max') ||
                                        $field === 'value';
                                    $isTime = str_contains($field, 'time');
                                    $isRequired = !in_array($field, $nullableFields, true);

                                    if ($field === 'is_active' && !$useOldRowValues) {
                                        $isActive = in_array($storedValue, [true, 1, '1', 't', 'true'], true);
                                        $rowValue = $isActive ? '1' : '0';
                                    }

                                    if ($isTime && $rowValue !== null && $rowValue !== '') {
                                        $rowValue = substr((string) $rowValue, 0, 5);
                                    }
                                @endphp

                                <td style="min-width:170px;">
                                    @if (isset($config['options'][$field]))
                                        <select name="{{ $field }}" class="form-control" form="{{ $updateFormId }}"
                                            @required($isRequired)>
                                            @if (!$isRequired)
                                                <option value="">Pilih {{ strtolower($label) }}</option>
                                            @endif

                                            @foreach ($config['options'][$field] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}" @selected((string) $rowValue === (string) $optionValue)>
                                                    {{ $optionLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif($field === 'description')
                                        <textarea name="{{ $field }}" class="form-control" rows="2" form="{{ $updateFormId }}">{{ $rowValue }}</textarea>
                                    @else
                                        <input type="{{ $isTime ? 'time' : ($isNumeric ? 'number' : 'text') }}"
                                            @if ($isNumeric) step="0.01" @endif name="{{ $field }}"
                                            value="{{ $rowValue }}" class="form-control" form="{{ $updateFormId }}"
                                            @required($isRequired)>
                                    @endif
                                </td>
                            @endforeach

                            <td style="white-space:nowrap;">
                                <div class="action-row">

                                    <a href="{{ route('admin.web.master.edit', [$type, $itemId]) }}"
                                        class="btn btn-primary">
                                        Edit
                                    </a>


                                    <form method="POST" action="{{ route('admin.web.master.delete', [$type, $itemId]) }}"
                                        onsubmit="return confirm('Yakin ingin menghapus data ini?')">

                                        @csrf

                                        <button type="submit" class="btn btn-danger">
                                            Hapus
                                        </button>

                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($config['fields']) + 2 }}">
                                <div class="empty-state">
                                    <strong>Belum ada data.</strong>
                                    Tambahkan data baru melalui form di atas.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
