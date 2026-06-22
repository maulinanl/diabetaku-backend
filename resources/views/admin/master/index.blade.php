@extends('admin.layouts.app')

@section('title', 'Master Data')

@section('content')
    <div class="card" style="margin-bottom:18px;">
        <h3 style="margin-top:0; color: var(--primary);">
            Master Data
        </h3>

        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            @foreach($masterMenus as $key => $menu)
                <a href="{{ route('admin.web.master.index', $key) }}"
                    class="btn {{ $type === $key ? 'btn-primary' : 'btn-outline' }}">
                    {{ $menu['title'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <h3 style="margin-top:0; color: var(--primary);">
            Tambah {{ $config['title'] }}
        </h3>

        <form method="POST" action="{{ route('admin.web.master.store', $type) }}">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:14px;">
                @foreach($config['fields'] as $field => $label)
                    <div>
                        <label style="display:block; margin-bottom:6px; color:var(--gray); font-size:13px;">
                            {{ $label }}
                        </label>

                        <input
                            type="{{ str_contains($field, 'time') ? 'time' : 'text' }}"
                            name="{{ $field }}"
                            value="{{ old($field) }}"
                            style="width:100%; padding:11px; border:1px solid var(--light); border-radius:8px;">
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;">
                Tambah Data
            </button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0; color: var(--primary);">
            Daftar {{ $config['title'] }}
        </h3>

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
                    <tr>
                        <form method="POST"
                            action="{{ route('admin.web.master.update', [$type, $item->{$config['primary_key']}]) }}">
                            @csrf

                            <td>{{ $item->{$config['primary_key']} }}</td>

                            @foreach($config['fields'] as $field => $label)
                                <td>
                                    <input
                                        type="{{ str_contains($field, 'time') ? 'time' : 'text' }}"
                                        name="{{ $field }}"
                                        value="{{ $item->{$field} }}"
                                        style="width:100%; padding:8px; border:1px solid var(--light); border-radius:8px;">
                                </td>
                            @endforeach

                            <td style="white-space:nowrap;">
                                <button type="submit" class="btn btn-primary">
                                    Simpan
                                </button>
                        </form>

                        <form method="POST"
                            action="{{ route('admin.web.master.delete', [$type, $item->{$config['primary_key']}]) }}"
                            style="display:inline;"
                            onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                            @csrf

                            <button type="submit" class="btn btn-danger">
                                Hapus
                            </button>
                        </form>
                            </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($config['fields']) + 2 }}">
                            Belum ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
