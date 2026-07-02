@extends('admin.layouts.app')

@section('title', 'Data Master')
@section('subtitle', 'Kelola data referensi yang digunakan oleh fitur aplikasi diabetAku.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Kategori Master Data</h3>
                <p class="card-desc">Pilih kategori data master yang ingin ditambah atau diperbarui.</p>
            </div>
        </div>

        <div class="menu-pills">
            @foreach($masterMenus as $key => $menu)
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

            <div class="form-grid">
                @foreach($config['fields'] as $field => $label)
                    <div class="form-group">
                        <label for="create_{{ $field }}">{{ $label }}</label>

                        @if($field === 'description')
                            <textarea id="create_{{ $field }}" name="{{ $field }}" class="form-control" rows="3"
                                placeholder="Masukkan {{ strtolower($label) }}">{{ old($field) }}</textarea>
                        @else
                            <input
                                id="create_{{ $field }}"
                                type="{{ str_contains($field, 'time') ? 'time' : (str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value' ? 'number' : 'text') }}"
                                step="{{ str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value' ? '0.01' : '' }}"
                                name="{{ $field }}"
                                value="{{ old($field) }}"
                                class="form-control"
                                placeholder="Masukkan {{ strtolower($label) }}">
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
                <p class="card-desc">Data yang sudah tersimpan dapat diperbarui langsung pada tabel.</p>
            </div>
            <span class="badge badge-blue">{{ $items->count() }} data</span>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>

                        @foreach($config['fields'] as $field => $label)
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
                        @endphp

                        <tr>
                            <form method="POST" action="{{ route('admin.web.master.update', [$type, $itemId]) }}">
                                @csrf

                                <td><span class="badge badge-blue">#{{ $itemId }}</span></td>

                                @foreach($config['fields'] as $field => $label)
                                    @php
                                        $value = data_get($item, $field, '');
                                    @endphp

                                    <td style="min-width:170px;">
                                        @if($field === 'description')
                                            <textarea name="{{ $field }}" class="form-control" rows="2">{{ old($field, $value) }}</textarea>
                                        @else
                                            <input
                                                type="{{ str_contains($field, 'time') ? 'time' : (str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value' ? 'number' : 'text') }}"
                                                step="{{ str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value' ? '0.01' : '' }}"
                                                name="{{ $field }}"
                                                value="{{ old($field, $value) }}"
                                                class="form-control">
                                        @endif
                                    </td>
                                @endforeach

                                <td style="white-space:nowrap;">
                                    <div class="action-row">
                                        <button type="submit" class="btn btn-primary">
                                            Simpan
                                        </button>
                            </form>

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
