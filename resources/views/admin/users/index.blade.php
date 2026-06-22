@extends('admin.layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="card">
        <h3 style="margin-top:0; color: var(--primary);">Daftar Pengguna</h3>

        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No. Telepon</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Ubah Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->full_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone_number ?? '-' }}</td>
                        <td>{{ $user->role_name ?? '-' }}</td>
                        <td>
                            <span class="badge
                                {{ $user->account_status === 'Aktif' ? 'badge-green' :
                                   ($user->account_status === 'Diblokir' ? 'badge-red' : 'badge-orange') }}">
                                {{ $user->account_status }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('admin.web.users.status', $user->user_id) }}" method="POST">
                                @csrf
                                <select name="account_status" style="padding:8px; border:1px solid var(--light); border-radius:8px;">
                                    <option value="Aktif" {{ $user->account_status === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="Nonaktif" {{ $user->account_status === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                                    <option value="Diblokir" {{ $user->account_status === 'Diblokir' ? 'selected' : '' }}>Diblokir</option>
                                </select>

                                <button class="btn btn-primary" type="submit">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada pengguna.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
