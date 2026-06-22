@extends('admin.layouts.app')

@section('title', 'Verifikasi Dokter')

@section('content')
    <div class="card">
        <h3 style="margin-top:0; color: var(--primary);">Daftar Dokter Menunggu Verifikasi</h3>

        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
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
                        <td>{{ $doctor->full_name }}</td>
                        <td>{{ $doctor->email }}</td>
                        <td>{{ $doctor->specialization_name ?? '-' }}</td>
                        <td>{{ $doctor->str_number }}</td>
                        <td>{{ $doctor->institution }}</td>
                        <td>
                            <span class="badge badge-orange">
                                {{ $doctor->verification_status }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('admin.web.doctors.verify', $doctor->doctor_id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button class="btn btn-primary" type="submit">Verifikasi</button>
                            </form>

                            <form action="{{ route('admin.web.doctors.reject', $doctor->doctor_id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Tidak ada dokter yang menunggu verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
