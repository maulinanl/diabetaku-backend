@extends('admin.layouts.app')

@section('title', 'Data Master')

@section('subtitle', 'Kelola data referensi yang digunakan oleh fitur aplikasi diabetAku.')

@section('content')

    @php
        $oldFormContext = old('_form_context');

        $nullableFields = ['dosage_form', 'value', 'unit', 'description', 'default_reminder_time'];
    @endphp


    {{-- PILIH KATEGORI --}}
    <div class="card">

        <div class="card-header">

            <div>
                <h3 class="card-title">
                    Kategori Master Data
                </h3>

                <p class="card-desc">
                    Pilih kategori data master yang ingin dikelola.
                </p>
            </div>

        </div>


        <div class="menu-pills">

            @foreach ($masterMenus as $key => $menu)
                <a href="{{ route('admin.web.master.index', $key) }}"
                    class="btn {{ $type === $key ? 'btn-primary' : 'btn-outline' }}">

                    {{ $menu['title'] }}

                </a>
            @endforeach

        </div>

    </div>




    {{-- FORM TAMBAH --}}
    <div class="card">


        <div class="card-header">

            <div>

                <h3 class="card-title">
                    Tambah {{ $config['title'] }}
                </h3>

                <p class="card-desc">
                    Masukkan data baru ke dalam sistem.
                </p>

            </div>

        </div>




        <form method="POST" action="{{ route('admin.web.master.store', $type) }}">

            @csrf

            <input type="hidden" name="_form_context" value="create">



            <div class="form-grid">


                @foreach ($config['fields'] as $field => $label)
                    @php

                        $isNumeric = str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value';

                        $isTime = str_contains($field, 'time');

                        $isRequired = !in_array($field, $nullableFields, true);

                        $value = $oldFormContext === 'create' ? old($field) : ($field === 'is_active' ? '1' : '');

                    @endphp




                    <div class="form-group">


                        <label>
                            {{ $label }}
                        </label>



                        @if (isset($config['options'][$field]))
                            <select name="{{ $field }}" class="form-control" @required($isRequired)>


                                @if (!$isRequired)
                                    <option value="">
                                        Pilih {{ strtolower($label) }}
                                    </option>
                                @endif



                                @foreach ($config['options'][$field] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>

                                        {{ $optionLabel }}

                                    </option>
                                @endforeach


                            </select>
                        @elseif($field === 'description')
                            <textarea name="{{ $field }}" class="form-control" rows="3">{{ $value }}</textarea>
                        @else
                            <input type="{{ $isTime ? 'time' : ($isNumeric ? 'number' : 'text') }}"
                                name="{{ $field }}" value="{{ $value }}" class="form-control"
                                @if ($isNumeric) step="0.01" @endif @required($isRequired)>
                        @endif



                    </div>
                @endforeach



            </div>



            <button type="submit" class="btn btn-primary" style="margin-top:16px">

                Tambah Data

            </button>



        </form>


    </div>







    {{-- FORM EDIT --}}
    @if (isset($editData))


        <div class="card">


            <div class="card-header">

                <div>

                    <h3 class="card-title">
                        Edit {{ $config['title'] }}
                    </h3>


                    <p class="card-desc">
                        Perbarui data yang dipilih.
                    </p>


                </div>

            </div>





            <form method="POST"
                action="{{ route('admin.web.master.update', [$type, data_get($editData, $config['primary_key'])]) }}">


                @csrf

                @method('PUT')




                <div class="form-grid">


                    @foreach ($config['fields'] as $field => $label)
                        @php

                            $value = data_get($editData, $field);

                            $isNumeric =
                                str_contains($field, 'min') || str_contains($field, 'max') || $field === 'value';

                            $isTime = str_contains($field, 'time');

                        @endphp





                        <div class="form-group">


                            <label>
                                {{ $label }}
                            </label>




                            @if (isset($config['options'][$field]))
                                <select name="{{ $field }}" class="form-control">


                                    @foreach ($config['options'][$field] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>

                                            {{ $optionLabel }}

                                        </option>
                                    @endforeach


                                </select>
                            @elseif($field === 'description')
                                <textarea name="{{ $field }}" class="form-control" rows="3">{{ $value }}</textarea>
                            @else
                                <input type="{{ $isTime ? 'time' : ($isNumeric ? 'number' : 'text') }}"
                                    name="{{ $field }}" value="{{ $value }}" class="form-control"
                                    @if ($isNumeric) step="0.01" @endif>
                            @endif



                        </div>
                    @endforeach



                </div>





                <div style="margin-top:16px">


                    <a href="{{ route('admin.web.master.index', $type) }}" class="btn btn-outline">

                        Batal

                    </a>



                    <button type="submit" class="btn btn-primary">

                        Simpan Perubahan

                    </button>


                </div>




            </form>


        </div>


    @endif







    {{-- TABEL DATA --}}

    <div class="card">


        <div class="card-header">


            <div>

                <h3 class="card-title">

                    Daftar {{ $config['title'] }}

                </h3>


                <p class="card-desc">

                    Data yang tersedia pada sistem.

                </p>


            </div>


            <span class="badge badge-blue">

                {{ $items->count() }} data

            </span>


        </div>







        <div class="table-responsive">


            <table>


                <thead>

                    <tr>

                        <th>No</th>


                        @foreach ($config['fields'] as $field => $label)
                            <th>
                                {{ $label }}
                            </th>
                        @endforeach


                        <th>
                            Aksi
                        </th>


                    </tr>

                </thead>





                <tbody>


                    @forelse($items as $item)


                        <tr>


                            <td>
                                {{ $loop->iteration }}
                            </td>




                            @foreach ($config['fields'] as $field => $label)
                                <td>

                                    @if (isset($config['options'][$field]))
                                        {{ $config['options'][$field][data_get($item, $field)] ?? '-' }}
                                    @else
                                        {{ data_get($item, $field) ?: '-' }}
                                    @endif


                                </td>
                            @endforeach





                            <td>


                                <div class="action-row">

                                    <a href="{{ route('admin.web.master.edit', [$type, data_get($item, $config['primary_key'])]) }}"
                                        class="btn btn-master-action btn-master-edit">
                                        Edit
                                    </a>


                                    <form class="action-form" method="POST"
                                        action="{{ route('admin.web.master.destroy', [$type, data_get($item, $config['primary_key'])]) }}"
                                        onsubmit="return confirm('Yakin ingin menghapus data ini?')">

                                        @csrf

                                        <button type="submit" class="btn btn-master-action btn-master-delete">
                                            {{ isset($config['fields']['is_active']) ? 'Nonaktifkan' : 'Hapus' }}
                                        </button>

                                    </form>

                                </div>


                            </td>



                        </tr>



                    @empty


                        <tr>


                            <td colspan="{{ count($config['fields']) + 2 }}">


                                <div class="empty-state">

                                    <strong>
                                        Belum ada data.
                                    </strong>

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
