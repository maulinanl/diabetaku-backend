@extends('admin.layouts.app')

@section('title', 'Dashboard Admin')
@section('subtitle', 'Ringkasan data pengguna, dokter, dan aktivitas pengelolaan sistem diabetAku.')

@section('content')
    <div class="grid">
        <div class="card stat-card">
            <div class="stat-icon">
                <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M16 11a4 4 0 10-8 0"></path>
                    <path d="M4 21a8 8 0 0116 0"></path>
                </svg>
            </div>
            <h3>{{ $totalUsers }}</h3>
            <p>Total Pengguna</p>
        </div>

        <div class="card stat-card">
            <div class="stat-icon">
                <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z"></path>
                    <path d="M12 11v4"></path>
                    <path d="M10 13h4"></path>
                </svg>
            </div>
            <h3>{{ $totalPatients }}</h3>
            <p>Total Pasien</p>
        </div>

        <div class="card stat-card">
            <div class="stat-icon">
                <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 3v18"></path>
                    <path d="M5 8h14"></path>
                    <path d="M7 21h10"></path>
                </svg>
            </div>
            <h3>{{ $totalDoctors }}</h3>
            <p>Total Dokter</p>
        </div>

        <div class="card stat-card">
            <div class="stat-icon">
                <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-5"></path>
                    <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3>{{ $pendingDoctors }}</h3>
            <p>Dokter Menunggu Verifikasi</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Pengguna Terbaru</h3>
                <p class="card-desc">Daftar akun terakhir yang masuk ke sistem.</p>
            </div>
            <a href="{{ route('admin.web.users.index') }}" class="btn btn-outline">Lihat Semua User</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($latestUsers as $user)
                        @php
                            $status = $user->account_status ?? 'Tidak diketahui';
                            $badgeClass = match ($status) {
                                'Aktif' => 'badge-green',
                                'Tidak Aktif', 'Terkunci' => 'badge-red',
                                default => 'badge-orange',
                            };
                        @endphp

                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="mini-avatar">{{ strtoupper(substr($user->full_name ?? 'U', 0, 1)) }}</div>
                                    <div>
                                        <div class="cell-title">{{ $user->full_name }}</div>
                                        <div class="cell-subtitle">ID: {{ $user->user_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge badge-blue">{{ $user->role_name ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td>
                                {{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <strong>Belum ada pengguna.</strong>
                                    Data pengguna baru akan tampil di bagian ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
