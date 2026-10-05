@extends('layouts.app')
@section('title', $title)

@section('content')
@php
    $isEdit = $record && $record->exists;
    $action = $isEdit ? route($routeName.'.update', $record->id) : route($routeName.'.store');
@endphp

<div class="card" style="max-width:820px">
    <div class="card-header"><h5 class="mb-0">{{ $isEdit ? 'Ubah' : 'Tambah' }} {{ $title }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            @foreach ($fields as $name => $f)
                @php
                    $type = $f['type'] ?? 'text';
                    $raw = isset($f['value']) ? $f['value']($record) : ($isEdit ? $record->{$name} : ($f['default'] ?? null));
                    if ($raw instanceof \BackedEnum) { $raw = $raw->value; }
                    if ($raw instanceof \DateTimeInterface) { $raw = $raw->format('Y-m-d'); }
                    $val = old($name, $raw);
                    $invalid = $errors->has($name) ? 'is-invalid' : '';
                @endphp

                <div class="mb-3">
                    @if ($type === 'checkbox')
                        @php $checked = session()->hasOldInput() ? old($name) : $raw; @endphp
                        <div class="form-check form-switch">
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="f_{{ $name }}" name="{{ $name }}" value="1" @checked($checked)>
                            <label class="form-check-label" for="f_{{ $name }}">{{ $f['label'] }}</label>
                        </div>
                    @else
                        <label class="form-label" for="f_{{ $name }}">{{ $f['label'] }}</label>

                        @if ($type === 'textarea')
                            <textarea id="f_{{ $name }}" name="{{ $name }}" rows="4" class="form-control {{ $invalid }}">{{ $val }}</textarea>

                        @elseif ($type === 'select')
                            <select id="f_{{ $name }}" name="{{ $name }}" class="form-select {{ $invalid }}">
                                <option value="">{{ $f['placeholder'] ?? '— pilih —' }}</option>
                                @foreach ($f['options'] as $k => $label)
                                    <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $label }}</option>
                                @endforeach
                            </select>

                        @elseif ($type === 'multiselect')
                            <select id="f_{{ $name }}" name="{{ $name }}[]" multiple size="6" class="form-select {{ $invalid }}">
                                @foreach ($f['options'] as $k => $label)
                                    <option value="{{ $k }}" @selected(in_array((string) $k, array_map('strval', (array) $val), true))>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Tahan Ctrl / Cmd untuk memilih lebih dari satu.</div>

                        @elseif ($type === 'file')
                            <input type="file" id="f_{{ $name }}" name="{{ $name }}" class="form-control {{ $invalid }}">
                            @if ($isEdit && $record->{$name})
                                <div class="form-text">Berkas saat ini tersimpan. Unggah baru untuk menggantinya.</div>
                            @endif

                        @elseif ($type === 'password')
                            <input type="password" id="f_{{ $name }}" name="{{ $name }}" class="form-control {{ $invalid }}" autocomplete="new-password">

                        @else
                            <input type="{{ $type }}" id="f_{{ $name }}" name="{{ $name }}" value="{{ $val }}"
                                   @if ($type === 'number') step="any" @endif class="form-control {{ $invalid }}">
                        @endif

                        @error($name) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @endif

                    @if (! empty($f['help'])) <div class="form-text">{{ $f['help'] }}</div> @endif
                </div>
            @endforeach

            <div class="d-flex gap-2">
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route($routeName.'.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
