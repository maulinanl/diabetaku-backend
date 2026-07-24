@extends('admin.layouts.app')

@section('title', 'Manajemen User')
@section('subtitle', 'Kelola status akun pengguna dan proses verifikasi pengguna.')

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
                <h3 class="card-title">
                    Filter Pengguna
                </h3>

                <p class="card-desc">
                    Cari pengguna berdasarkan nama/email, role, atau status akun.
                </p>
            </div>
        </div>


        <form method="GET" action="{{ route('admin.web.users.index') }}" class="filter-form">


            <div class="form-group">

                <label>
                    Pencarian
                </label>

                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="Cari nama atau email">

            </div>



            <div class="form-group">

                <label>
                    Role
                </label>


                <select name="role" class="form-control">

                    <option value="">
                        Semua Role
                    </option>


                    @foreach ($roles as $role)
                        <option value="{{ $role->role_id }}" {{ request('role') == $role->role_id ? 'selected' : '' }}>

                            {{ $role->role_name }}

                        </option>
                    @endforeach

                </select>

            </div>



            <div class="form-group">

                <label>
                    Status Akun
                </label>


                <select name="status" class="form-control">

                    <option value="">
                        Semua Status
                    </option>


                    @foreach (['Aktif', 'Tidak Aktif', 'Terkunci'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>

                            {{ $status }}

                        </option>
                    @endforeach


                </select>

            </div>



            <div class="action-row">

                <button class="btn btn-primary">
                    Terapkan
                </button>


                <a href="{{ route('admin.web.users.index') }}" class="btn btn-outline">

                    Reset

                </a>

            </div>


        </form>

    </div>




    <div class="card">


        <div class="card-header">

            <div>

                <h3 class="card-title">
                    Daftar Pengguna
                </h3>


                <p class="card-desc">
                    Menampilkan {{ $users->count() }} dari {{ $users->total() }} pengguna.
                </p>


            </div>


        </div>



        <div class="table-responsive">


            <table>


                <thead>

                    <tr>

                        <th>
                            Pengguna
                        </th>


                        <th>
                            No. Telepon
                        </th>


                        <th>
                            Role
                        </th>


                        <th>
                            Verifikasi Email
                        </th>


                        <th>
                            Status Akun
                        </th>


                        <th>
                            Verifikasi Dokter
                        </th>


                        <th>
                            Ubah Status
                        </th>


                        <th>
                            Reset Password
                        </th>


                    </tr>


                </thead>


                <tbody>



                    @forelse($users as $user)


                        @php

                            $canChangeStatus = true;

                            if (is_null($user->email_verified_at)) {
                                $canChangeStatus = false;
                            }

                            if (
                                ($user->role_name ?? '') === 'Dokter' &&
                                $user->doctor_verification_status !== 'Disetujui'
                            ) {
                                $canChangeStatus = false;
                            }
                        @endphp

                        <tr>


                            <td>

                                <div class="user-cell">

                                    <div class="mini-avatar">

                                        {{ strtoupper(substr($user->full_name ?? 'U', 0, 1)) }}

                                    </div>


                                    <div>

                                        <div class="cell-title">

                                            {{ $user->full_name }}

                                        </div>


                                        <div class="cell-subtitle">

                                            {{ $user->email }}

                                        </div>


                                    </div>

                                </div>

                            </td>




                            <td>

                                {{ $user->phone_number ?? '-' }}

                            </td>




                            <td>

                                <span class="status-text">

                                    {{ $user->role_name ?? '-' }}

                                </span>

                            </td>





                            <td>


                                @if (is_null($user->email_verified_at))
                                    <span class="status-text status-warning">

                                        Belum Terverifikasi

                                    </span>
                                @else
                                    <span class="status-text status-success">

                                        Terverifikasi

                                    </span>
                                @endif


                            </td>





                            <td>


                                <span class="status-text">

                                    {{ $user->account_status }}

                                </span>


                            </td>







                            <td>


                                @if (($user->role_name ?? '') === 'Dokter')
                                    <span class="status-text">

                                        {{ $user->doctor_verification_status ?? 'Menunggu' }}

                                    </span>



                                    @if ($user->doctor_verification_status === 'Ditolak')
                                        <form
                                            action="{{ route('admin.web.doctors.reset-verification', $user->doctor_id) }}"
                                            method="POST" style="margin-top:8px;">

                                            @csrf


                                            <button type="submit" class="btn btn-outline">

                                                Ajukan Ulang

                                            </button>


                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted">

                                        -

                                    </span>
                                @endif



                            </td>








                            <td>


                                @if ($canChangeStatus)
                                    <form action="{{ route('admin.web.users.status', $user->user_id) }}" method="POST"
                                        class="inline-form">

                                        @csrf


                                        <select name="account_status" class="form-control">


                                            <option value="Aktif"
                                                {{ $user->account_status == 'Aktif' ? 'selected' : '' }}>

                                                Aktif

                                            </option>


                                            <option value="Tidak Aktif"
                                                {{ $user->account_status == 'Tidak Aktif' ? 'selected' : '' }}>

                                                Tidak Aktif

                                            </option>


                                            <option value="Terkunci"
                                                {{ $user->account_status == 'Terkunci' ? 'selected' : '' }}>

                                                Terkunci

                                            </option>


                                        </select>



                                        <button class="btn btn-primary">

                                            Simpan

                                        </button>



                                    </form>
                                @else
                                    <span class="status-text">

                                        {{ $user->account_status }}

                                    </span>
                                @endif



                            </td>







                            <td>


                                <form action="{{ route('admin.web.users.send-reset-link', $user->user_id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Kirim link reset password ke email {{ $user->email }}?')">


                                    @csrf


                                    <button class="btn btn-outline">

                                        Kirim Link Reset

                                    </button>


                                </form>



                            </td>



                        </tr>



                    @empty


                        <tr>

                            <td colspan="8">

                                <div class="empty-state">

                                    <strong>
                                        Pengguna tidak ditemukan.
                                    </strong>

                                    Coba ubah kata kunci atau filter pencarian.


                                </div>


                            </td>


                        </tr>
                    @endforelse



                </tbody>


            </table>


        </div>






        @if ($users->hasPages())


            <div style="
display:flex;
justify-content:space-between;
align-items:center;
margin-top:16px;
">


                <div class="text-muted" style="font-size:12px;">

                    Menampilkan

                    {{ $users->firstItem() }}

                    -

                    {{ $users->lastItem() }}

                    dari

                    {{ $users->total() }}

                    pengguna


                </div>



                <div class="action-row">


                    @if ($users->currentPage() > 1)
                        <a href="{{ route(
                            'admin.web.users.index',
                            array_merge(request()->query(), [
                                'page' => $users->currentPage() - 1,
                            ]),
                        ) }}"
                            class="btn btn-outline">

                            Sebelumnya

                        </a>
                    @endif





                    @for ($page = 1; $page <= $users->lastPage(); $page++)
                        <a href="{{ $users->url($page) }}"
                            class="btn {{ $users->currentPage() == $page ? 'btn-primary' : 'btn-outline' }}">

                            {{ $page }}

                        </a>
                    @endfor





                    @if ($users->hasMorePages())
                        <a href="{{ route(
                            'admin.web.users.index',
                            array_merge(request()->query(), [
                                'page' => $users->currentPage() + 1,
                            ]),
                        ) }}"
                            class="btn btn-primary">

                            Berikutnya

                        </a>
                    @endif



                </div>


            </div>


        @endif



    </div>


@endsection
