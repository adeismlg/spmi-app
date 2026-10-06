@extends('layouts.app')
@section('title', $title)

@section('content')
<div class="card crud-list-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $title }}</h5>
        @if ($canManage && Route::has($routeName.'.create'))
            <a href="{{ route($routeName.'.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah</a>
        @endif
    </div>

    @if (! empty($filters))
        <form method="GET" class="card-body border-bottom py-2 d-flex flex-wrap gap-2 align-items-center">
            @foreach ($filters as $name => $f)
                <select name="{{ $name }}" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">{{ $f['label'] }}: semua</option>
                    @foreach ($f['options'] as $k => $label)
                        <option value="{{ $k }}" @selected((string) request($name) === (string) $k)>{{ $label }}</option>
                    @endforeach
                </select>
            @endforeach
            @if (request()->hasAny(array_keys($filters)))
                <a href="{{ url()->current() }}" class="btn btn-sm btn-link">Reset</a>
            @endif
        </form>
    @endif

    <div class="card-body p-3">
        <div class="crud-table-shell table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:50px">#</th>
                    @foreach ($columns as $c) <th>{{ $c['label'] }}</th> @endforeach
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $rows->firstItem() + $loop->index }}</td>
                        @foreach ($columns as $c)
                            <td>
                                @if (isset($c['badge']))
                                    @php [$text, $color] = $c['badge']($row); @endphp
                                    <span class="badge bg-{{ $color }}">{{ $text }}</span>
                                @else
                                    {{ $c['value']($row) }}
                                @endif
                            </td>
                        @endforeach
                        <td class="text-end text-nowrap">
                            @foreach ($actions as $a)
                                @continue(isset($a['visible']) && ! $a['visible']($row))
                                @php
                                    $label = $a['label'] instanceof \Closure ? $a['label']($row) : $a['label'];
                                    $url = $a['route']($row);
                                    $cls = 'btn btn-sm '.($a['class'] ?? 'btn-outline-secondary');
                                @endphp
                                @if (($a['method'] ?? 'get') === 'post')
                                    <form method="POST" action="{{ $url }}" class="d-inline"
                                          @isset($a['confirm']) onsubmit="return confirm('{{ $a['confirm'] }}')" @endisset>
                                        @csrf
                                        <button class="{{ $cls }}">{{ $label }}</button>
                                    </form>
                                @else
                                    <a href="{{ $url }}" class="{{ $cls }}">{{ $label }}</a>
                                @endif
                            @endforeach

                            @if ($canManage && Route::has($routeName.'.edit'))
                                <a href="{{ route($routeName.'.edit', $row->id) }}" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                            @endif
                            @if ($canManage && Route::has($routeName.'.destroy'))
                                <form method="POST" action="{{ route($routeName.'.destroy', $row->id) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus data ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 2 }}" class="text-center text-muted py-5">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($rows->hasPages())
        <div class="card-footer bg-white">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
