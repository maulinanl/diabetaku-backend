@extends('admin.layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="card">
        <h3 style="margin-top:0;color:var(--primary)">
            Ringkasan Sistem
        </h3>

        <p>
            Dashboard ini digunakan untuk memantau jumlah pengguna,
            memverifikasi dokter, dan mengelola akun pada sistem diabetAku.
        </p>
    </div>

    <br>

    <div class="grid">
        <div class="card stat-card">
            <h3>{{ $totalUsers }}</h3>
            <p>Total Pengguna</p>
        </div>

        <div class="card stat-card">
            <h3>{{ $totalPatients }}</h3>
            <p>Total Pasien</p>
        </div>

        <div class="card stat-card">
            <h3>{{ $totalDoctors }}</h3>
            <p>Total Dokter</p>
        </div>

        <div class="card stat-card">
            <h3>{{ $pendingDoctors }}</h3>
            <p>Dokter Menunggu Verifikasi</p>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0; color: var(--primary);">
            Pengguna Terbaru
        </h3>

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
                                'Diblokir' => 'badge-red',
                                default => 'badge-orange',
                            };
                        @endphp

                        <tr>
                            <td>{{ $user->full_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role_name ?? '-' }}</td>
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
                            <td colspan="5">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
    