@php
    $on = fn (string $pattern) => request()->routeIs($pattern) ? 'active' : '';
@endphp

<ul class="nav flex-column menu">
    <li class="nav-item"><a class="nav-link {{ $on('dashboard') }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

    <li class="menu-header">Penetapan</li>
    <li class="nav-item"><a class="nav-link {{ $on('documents.*') }}" href="{{ route('documents.index') }}"><i class="bi bi-folder2-open"></i> Dokumen Mutu</a></li>
    @role('admin_spmi')
        <li class="nav-item"><a class="nav-link {{ $on('standards.*') }}" href="{{ route('standards.index') }}"><i class="bi bi-journal-check"></i> Standar Mutu</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('statements.*') }}" href="{{ route('statements.index') }}"><i class="bi bi-card-text"></i> Pernyataan Standar</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('indicators.*') }}" href="{{ route('indicators.index') }}"><i class="bi bi-bullseye"></i> Indikator & Target</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('activities.*') }}" href="{{ route('activities.index') }}"><i class="bi bi-list-check"></i> Kegiatan Indikator</a></li>
    @endrole

    @hasanyrole('admin_spmi|auditee')
        <li class="menu-header">Pelaksanaan</li>
        <li class="nav-item"><a class="nav-link {{ $on('self-evaluations.*') }}" href="{{ route('self-evaluations.index') }}"><i class="bi bi-clipboard2-check"></i> Evaluasi Diri</a></li>
    @endhasanyrole

    <li class="menu-header">Evaluasi</li>
    @hasanyrole('admin_spmi|auditor|pimpinan')
        <li class="nav-item"><a class="nav-link {{ $on('audits.*') }}" href="{{ route('audits.index') }}"><i class="bi bi-search"></i> Audit Mutu Internal</a></li>
    @endhasanyrole
    <li class="nav-item"><a class="nav-link {{ $on('findings.*') }}" href="{{ route('findings.index') }}"><i class="bi bi-exclamation-triangle"></i> Temuan</a></li>

    <li class="menu-header">Pengendalian</li>
    <li class="nav-item"><a class="nav-link {{ $on('corrective-actions.*') }}" href="{{ route('corrective-actions.index') }}"><i class="bi bi-wrench-adjustable"></i> Tindak Lanjut (RTL)</a></li>
    @hasanyrole('admin_spmi|pimpinan')
        <li class="nav-item"><a class="nav-link {{ $on('management-reviews.*') }}" href="{{ route('management-reviews.index') }}"><i class="bi bi-people"></i> Tinjauan Manajemen</a></li>
    @endhasanyrole

    <li class="menu-header">Laporan</li>
    <li class="nav-item"><a class="nav-link {{ $on('reports.*') }}" href="{{ route('reports.index') }}"><i class="bi bi-file-earmark-arrow-down"></i> Ekspor Laporan</a></li>

    @role('admin_spmi')
        <li class="menu-header">Pengaturan</li>
        <li class="nav-item"><a class="nav-link {{ $on('cycles.*') }}" href="{{ route('cycles.index') }}"><i class="bi bi-arrow-repeat"></i> Siklus PPEPP</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('units.*') }}" href="{{ route('units.index') }}"><i class="bi bi-diagram-3"></i> Unit / Prodi</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('users.*') }}" href="{{ route('users.index') }}"><i class="bi bi-person-gear"></i> Pengguna</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('semester-windows.*') }}" href="{{ route('semester-windows.index') }}"><i class="bi bi-calendar2-week"></i> Jadwal Semester</a></li>
        <li class="nav-item"><a class="nav-link {{ $on('reopen-requests.*') }}" href="{{ route('reopen-requests.index') }}"><i class="bi bi-unlock"></i> Permintaan Pembukaan</a></li>
    @endrole
</ul>
