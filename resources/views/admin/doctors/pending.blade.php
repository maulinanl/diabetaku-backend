@extends('admin.layouts.app')

@section('title', 'Verifikasi Dokter')
@section('subtitle', 'Tinjau akun dokter yang sudah mendaftar sebelum diberi akses penuh ke aplikasi.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Dokter Menunggu Verifikasi</h3>
                <p class="card-desc">Pastikan data STR, spesialisasi, dan institusi dokter sudah sesuai sebelum disetujui.</p>
            </div>
            <span class="badge badge-orange">{{ $doctors->count() }} menunggu</span>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Dokter</th>
                        <th>Kontak</th>
                        <th>Spesialisasi</th>
                        <th>STR</th>
                        <th>Institusi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doctor)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="mini-avatar">{{ strtoupper(substr($doctor->full_name ?? 'D', 0, 1)) }}</div>
                                    <div>
                                        <div class="cell-title">{{ $doctor->full_name }}</div>
                                        <div class="cell-subtitle">Daftar {{ $doctor->created_at ? \Carbon\Carbon::parse($doctor->created_at)->format('d/m/Y') : '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $doctor->email }}</div>
                                <div class="cell-subtitle">{{ $doctor->phone_number ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-blue">{{ $doctor->specialization_name ?? 'Belum diisi' }}</span>
                            </td>
                            <td class="nowrap">{{ $doctor->str_number }}</td>
                            <td>{{ $doctor->institution ?? '-' }}</td>
                            <td>
                                <span class="badge badge-orange">
                                    {{ $doctor->verification_status }}
                                </span>
                            </td>
                            <td>
                                <div class="action-row">
                                    <form action="{{ route('admin.web.doctors.verify', $doctor->doctor_id) }}" method="POST"
                                        onsubmit="return confirm('Verifikasi dokter {{ $doctor->full_name }}?')">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">Verifikasi</button>
                                    </form>

                                    <form action="{{ route('admin.web.doctors.reject', $doctor->doctor_id) }}" method="POST"
                                        onsubmit="return confirm('Tolak verifikasi dokter {{ $doctor->full_name }}?')">
                                        @csrf
                                        <button class="btn btn-danger" type="submit">Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <strong>Tidak ada dokter menunggu verifikasi.</strong>
                                    Semua pendaftaran dokter sudah diproses.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
