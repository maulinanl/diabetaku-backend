@extends('admin.layouts.app')

@section('title', 'Manajemen User')
@section('subtitle', 'Kelola status akun pengguna dan kirim link reset password bila diperlukan.')

@section('content')
    @if (session('temporary_password'))
        <div class="alert alert-success">
            Password sementara untuk <b>{{ session('reset_user_name') }}</b>:
            <b>{{ session('temporary_password') }}</b>
            <br>
            Harap segera diberikan ke pengguna dan minta pengguna mengganti password.
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Filter Pengguna</h3>
                <p class="card-desc">Cari pengguna berdasarkan nama/email, role, atau status akun.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.web.users.index') }}" class="filter-form">
            <div class="form-group">
                <label for="q">Pencarian</label>
                <input id="q" type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="Cari nama atau email">
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control">
                    <option value="">Semua Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->role_id }}" {{ (string) request('role') === (string) $role->role_id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Semua Status</option>
                    @foreach(['Menunggu Verifikasi', 'Aktif', 'Tidak Aktif', 'Terkunci'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                <a href="{{ route('admin.web.users.index') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Pengguna</h3>
                <p class="card-desc">Menampilkan {{ $users->count() }} dari {{ $users->total() }} pengguna.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>No. Telepon</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th>Verifikasi Dokter</th>
                        <th>Ubah Status</th>
                        <th>Reset Password</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        @php
                            $accountBadgeClass = match ($user->account_status) {
                                'Aktif' => 'badge-green',
                                'Tidak Aktif', 'Terkunci' => 'badge-red',
                                default => 'badge-orange',
                            };

                            $doctorBadgeClass = match ($user->doctor_verification_status) {
                                'Disetujui' => 'badge-green',
                                'Ditolak' => 'badge-red',
                                default => 'badge-orange',
                            };
                        @endphp

                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="mini-avatar">{{ strtoupper(substr($user->full_name ?? 'U', 0, 1)) }}</div>
                                    <div>
                                        <div class="cell-title">{{ $user->full_name }}</div>
                                        <div class="cell-subtitle">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->phone_number ?? '-' }}</td>
                            <td>
                                <span class="badge badge-blue">{{ $user->role_name ?? '-' }}</span>
                            </td>

                            <td>
                                @if (is_null($user->email_verified_at))
                                    <span class="badge badge-orange">Email Belum Terverifikasi</span>
                                @else
                                    <span class="badge {{ $accountBadgeClass }}">
                                        {{ $user->account_status }}
                                    </span>
                                @endif
                            </td>

                            <td class="doctor-verification-td">
                                @if (($user->role_name ?? '') === 'Dokter')
                                    <span class="badge {{ $doctorBadgeClass }}">
                                        {{ $user->doctor_verification_status ?? 'Menunggu' }}
                                    </span>

                                    @if ($user->doctor_verification_status === 'Ditolak')
                                        <form class="doctor-reset-form"
                                            action="{{ route('admin.web.doctors.reset-verification', $user->doctor_id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Reset pengajuan dokter {{ $user->full_name }} ke status Menunggu Verifikasi?')">
                                            @csrf

                                            <button type="submit" class="btn btn-reset-doctor">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M3 12a9 9 0 0 1 15.3-6.3L21 8"></path>
                                                    <path d="M21 3v5h-5"></path>
                                                    <path d="M21 12a9 9 0 0 1-15.3 6.3L3 16"></path>
                                                    <path d="M8 16H3v5"></path>
                                                </svg>
                                                Ajukan Ulang
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                <form action="{{ route('admin.web.users.status', $user->user_id) }}" method="POST" class="inline-form">
                                    @csrf

                                    <select name="account_status" class="form-control" style="min-width:170px;">
                                        @foreach(['Menunggu Verifikasi', 'Aktif', 'Tidak Aktif', 'Terkunci'] as $status)
                                            <option value="{{ $status }}" {{ $user->account_status === $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <button class="btn btn-primary" type="submit">
                                        Simpan
                                    </button>
                                </form>
                            </td>

                            <td>
                                <form action="{{ route('admin.web.users.send-reset-link', $user->user_id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Kirim link reset password ke email {{ $user->email }}?')">
                                    @csrf

                                    <button type="submit" class="btn btn-outline">
                                        Kirim Link Reset
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <strong>Pengguna tidak ditemukan.</strong>
                                    Coba ubah kata kunci atau filter pencarian.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="action-row" style="justify-content:space-between; margin-top:16px;">
                <div class="text-muted" style="font-size:12px;">
                    Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}
                </div>

                <div class="action-row">
                    @if ($users->previousPageUrl())
                        <a href="{{ $users->previousPageUrl() }}" class="btn btn-outline">Sebelumnya</a>
                    @endif

                    @if ($users->nextPageUrl())
                        <a href="{{ $users->nextPageUrl() }}" class="btn btn-primary">Berikutnya</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
