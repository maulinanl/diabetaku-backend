@extends('admin.layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="card">
        <h3 style="margin-top:0; color: var(--primary);">
            Daftar Pengguna
        </h3>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. Telepon</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th>Verifikasi Dokter</th>
                        <th>Ubah Status Akun</th>
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
                                @if(is_null($user->email_verified_at))
                                    <span class="badge badge-orange">
                                        Menunggu Verifikasi Email
                                    </span>
                                @else
                                    <span class="badge
                                        {{ $user->account_status === 'Aktif' ? 'badge-green' :
                                           ($user->account_status === 'Diblokir' ? 'badge-red' : 'badge-orange') }}">
                                        {{ $user->account_status }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if(($user->role_name ?? '') === 'Dokter')
                                    <span class="badge
                                        {{ $user->doctor_verification_status === 'Disetujui' ? 'badge-green' :
                                           ($user->doctor_verification_status === 'Ditolak' ? 'badge-red' : 'badge-orange') }}">
                                        {{ $user->doctor_verification_status ?? 'Menunggu' }}
                                    </span>
                                @else
                                    <span style="color: var(--gray);">-</span>
                                @endif
                            </td>

                            <td>
                                <form action="{{ route('admin.web.users.status', $user->user_id) }}"
                                    method="POST"
                                    style="display:flex; gap:8px; align-items:center;">
                                    @csrf

                                    <select name="account_status"
                                        style="padding:8px; border:1px solid var(--light); border-radius:8px;">
                                        <option value="Aktif" {{ $user->account_status === 'Aktif' ? 'selected' : '' }}>
                                            Aktif
                                        </option>
                                        <option value="Nonaktif" {{ $user->account_status === 'Nonaktif' ? 'selected' : '' }}>
                                            Nonaktif
                                        </option>
                                        <option value="Diblokir" {{ $user->account_status === 'Diblokir' ? 'selected' : '' }}>
                                            Diblokir
                                        </option>
                                    </select>

                                    <button class="btn btn-primary" type="submit">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                Belum ada pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
