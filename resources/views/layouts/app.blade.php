<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SPMI') — {{ config('app.name', 'SPMI') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- Catatan: untuk memakai Sneat, ganti file layout ini dengan layout Sneat Anda dan pertahankan
         @yield('title'), @yield('content'), @stack('scripts') serta @include('partials.alerts'). --}}
    <style>
        :root { --bs-primary:#5b3fc6; --bs-primary-rgb:91,63,198; --bs-link-color:#5b3fc6; --bs-body-bg:#f7f7fb; --sidebar-w:220px; --sidebar-bg:#fff; --sidebar-muted:#52566a; --spmi-accent:#5b3fc6; --spmi-accent-soft:#f0edff; }
        body { font-family:'Public Sans',system-ui,sans-serif; color:#34364a; background:#f7f7fb; }
        .btn-primary { --bs-btn-bg:#5b3fc6; --bs-btn-border-color:#5b3fc6; --bs-btn-hover-bg:#4930a8; --bs-btn-hover-border-color:#4930a8; --bs-btn-active-bg:#3c278e; --bs-btn-active-border-color:#3c278e; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:var(--sidebar-w); background:var(--sidebar-bg); border-right:1px solid #ebe9f2; overflow-y:auto; z-index:1030; color:#34364a; scrollbar-width:thin; scrollbar-color:#d8d2ef transparent; }
        .brand { min-height:61px; display:flex; align-items:center; gap:.7rem; padding:.65rem 1rem; font-size:1.35rem; font-weight:700; color:#39227f; background:#fff; letter-spacing:-.04em; }
        .brand-mark { width:34px; height:34px; display:grid; place-items:center; border-radius:50%; color:#fff; background:#5b3fc6; box-shadow:inset 0 0 0 3px rgba(255,255,255,.24); font-size:1.1rem; }
        .sidebar-meta { padding:.8rem .9rem .9rem; border-bottom:1px solid #efedf5; }
        .sidebar-date { display:block; text-align:center; padding:.22rem .4rem; margin-bottom:.75rem; color:#51409a; background:#f2efff; font-size:.72rem; font-weight:600; }
        .sidebar-user { font-size:.72rem; line-height:1.45; color:#626579; }
        .sidebar-user strong { display:block; font-size:.74rem; margin-bottom:.2rem; color:#303246; }
        .menu { padding:0 0 1.5rem; }
        .menu .nav-link { color:var(--sidebar-muted); border-radius:.45rem; border-left:0; padding:.62rem .9rem; display:flex; align-items:center; gap:.65rem; margin:.12rem .55rem; font-size:.79rem; font-weight:600; }
        .menu .nav-link:hover { background:#f4f1ff; color:#5136b4; }
        .menu .nav-link.active { background:#5b3fc6; color:#fff; box-shadow:0 .2rem .45rem rgba(91,63,198,.2); }
        .menu .nav-link i { width:1rem; text-align:center; font-size:.9rem; }
        .menu-header { font-size:.68rem; text-transform:uppercase; letter-spacing:.08em; color:#9698a8; padding:1.05rem .9rem .35rem; }
        .main { margin-left:var(--sidebar-w); min-height:100vh; }
        .topbar { min-height:62px; background:#fff; padding:.55rem 1.25rem; position:sticky; top:0; z-index:1020; border-bottom:1px solid #ebe9f2; }
        .institution-title { margin:0; color:#27283b; font-size:1.3rem; font-weight:700; letter-spacing:-.035em; }
        .topbar-actions { color:#4b4c60; }
        .topbar-actions .btn-link { color:inherit; text-decoration:none; font-size:.82rem; font-weight:600; }
        .breadcrumb-bar { padding:.4rem 1.25rem .7rem; color:#77798a; font-size:.78rem; }
        .breadcrumb-bar a { color:#77798a; text-decoration:none; }
        .breadcrumb-bar a:hover { color:var(--spmi-accent); }
        .content { padding:1rem 1.25rem 2rem; }
        .card { border:1px solid #e7e5ef; box-shadow:0 .15rem .55rem rgba(47,38,89,.045); border-radius:.65rem; background:#fff; }
        .card-header { background:#fff; border-bottom:1px solid #eeecf4; color:#303047; padding:.8rem .95rem; }
        .table > :not(caption) > * > * { padding:.58rem .65rem; }
        .table { color:#66687a; }
        .table thead th { font-size:.76rem; font-weight:700; color:#fff; background:#392579; white-space:normal; border-color:#392579; }
        .table tbody td { border-color:#eeecf4; font-size:.82rem; }
        .table-hover tbody tr:hover > * { --bs-table-bg-state:#f8f6ff; }
        .crud-list-card { overflow:hidden; border-radius:.9rem; }
        .crud-table-shell { overflow:hidden; border-radius:.6rem; }
        .crud-table-shell .table thead th:first-child { border-top-left-radius:.6rem; }
        .crud-table-shell .table thead th:last-child { border-top-right-radius:.6rem; }
        .form-control, .form-select, .input-group-text { border-radius:.45rem; border-color:#dedce8; }
        .form-control:focus, .form-select:focus { border-color:#9d8be5; box-shadow:0 0 0 .2rem rgba(91,63,198,.13); }
        .btn { border-radius:.45rem; }
        .alert { border-radius:.55rem; }
        .stage-pill { font-size:.72rem; padding:.3rem .65rem; border-radius:2rem; }
        .sidebar-backdrop { display:none; position:fixed; inset:0; z-index:1025; background:rgba(16,27,37,.52); }
        .sidebar-backdrop.show { display:block; }
        .dashboard-banner { position:relative; display:flex; align-items:center; justify-content:space-between; gap:1.5rem; min-height:130px; overflow:hidden; padding:1.4rem 1.6rem; border-radius:1rem; color:#fff; background:linear-gradient(110deg,#392579 0%,#5b3fc6 58%,#7664e8 100%); box-shadow:0 .35rem .9rem rgba(71,48,161,.2); }
        .dashboard-banner::after { position:absolute; top:-3.1rem; right:8rem; width:12rem; height:12rem; border:1px solid rgba(255,255,255,.08); border-radius:50%; background:rgba(255,255,255,.08); content:""; pointer-events:none; }
        .dashboard-welcome { position:relative; z-index:1; display:flex; align-items:center; gap:1rem; }
        .dashboard-avatar { width:3.8rem; height:3.8rem; display:grid; flex:0 0 auto; place-items:center; border:2px solid rgba(255,255,255,.45); border-radius:50%; color:#fff; background:rgba(255,255,255,.2); font-size:1.8rem; }
        .dashboard-date { display:block; margin-bottom:.2rem; color:rgba(255,255,255,.78); font-size:.75rem; }
        .dashboard-banner h2 { margin:0; font-size:1.45rem; font-weight:700; }
        .dashboard-banner p { margin:.35rem 0 0; color:rgba(255,255,255,.82); font-size:.8rem; }
        .dashboard-role { display:inline-flex; margin-top:.4rem; padding:.18rem .6rem; border-radius:2rem; color:#fff; background:rgba(255,255,255,.2); font-size:.7rem; font-weight:600; }
        .dashboard-stage { position:relative; z-index:1; display:flex; flex-direction:column; gap:.2rem; min-width:205px; padding:.7rem .9rem; border:1px solid rgba(255,255,255,.2); border-radius:.7rem; color:#fff; background:rgba(255,255,255,.15); backdrop-filter:blur(8px); }
        .dashboard-stage-label { color:rgba(255,255,255,.82); font-size:.65rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
        .dashboard-stage strong { font-size:1rem; line-height:1.25; }
        .dashboard-stage small { color:rgba(255,255,255,.78); }
        .dashboard-filters { padding:.8rem 1rem; border:1px solid #e7e5ef; border-radius:.65rem; background:#fff; }
        .dashboard-filters .form-label { color:#737487; }
        .dashboard-stat { overflow:hidden; }
        .dashboard-stat .card-body { min-height:146px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.55rem; padding:1rem .75rem; text-align:center; background:#fbfcff; }
        .dashboard-stat-label { color:#77798a; font-size:.78rem; }
        .dashboard-stat-value { color:#151625; font-size:1.65rem; font-weight:700; line-height:1.15; }
        .dashboard-stat-icon { width:3rem; height:3rem; display:grid; flex:0 0 auto; place-items:center; border-radius:.8rem; font-size:1.35rem; }
        .dashboard-stat-icon.primary { color:#5b3fc6; background:#f0edff; }
        .dashboard-stat-icon.info { color:#3985e8; background:#edf5ff; }
        .dashboard-stat-icon.warning { color:#d89016; background:#fff5df; }
        .dashboard-stat-icon.success { color:#159b82; background:#e7f8f4; }
        .dashboard-stat-icon.danger { color:#d84f72; background:#fff0f3; }
        .dashboard-notice { display:flex; align-items:center; gap:.8rem; margin-bottom:1.25rem; padding:.9rem 1rem; border:1px solid #ffc8d2; border-radius:.75rem; color:#8d2940; background:#fff5f7; }
        .dashboard-notice > i { flex:0 0 auto; font-size:1.1rem; }
        .dashboard-notice strong { display:block; font-size:.85rem; }
        .dashboard-notice p { margin:.15rem 0 0; font-size:.75rem; }
        .dashboard-notice a { margin-left:auto; color:#8d2940; font-size:.75rem; font-weight:700; text-decoration:none; white-space:nowrap; }
        .dashboard-chart-body { min-height:300px; }
        .dashboard-list .list-group-item { padding:.8rem 1rem; border-color:#eeecf4; color:#55576a; }
        @media (max-width:991px) {
            .sidebar { transform:translateX(-100%); transition:transform .2s ease; }
            .sidebar.show { transform:none; }
            .main { margin-left:0; }
            .content { padding:.9rem .85rem 1.5rem; }
            .topbar { padding:.55rem .85rem; }
            .breadcrumb-bar { padding:.35rem .85rem .6rem; }
            .institution-title { font-size:1.05rem; }
            .dashboard-banner { align-items:stretch; flex-direction:column; padding:1rem; }
            .dashboard-stage { align-self:flex-start; }
            .dashboard-avatar { width:3.2rem; height:3.2rem; }
            .dashboard-banner h2 { font-size:1.2rem; }
            .dashboard-notice { align-items:flex-start; flex-wrap:wrap; }
            .dashboard-notice a { margin-left:1.9rem; }
        }
    </style>
</head>
<body>
@php
    // Fallback: layout tetap jalan walau AppServiceProvider belum ditimpa (view composer tidak terdaftar).
    $activeCycle = $activeCycle ?? \App\Models\Cycle::current();
@endphp
<aside class="sidebar" id="sidebar">
    <a class="brand text-decoration-none" href="{{ route('dashboard') }}">
        <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
        <span>SPMI</span>
    </a>
    <div class="sidebar-meta">
        <span class="sidebar-date">{{ now()->format('d/m/Y H:i') }}</span>
        <div class="sidebar-user">
            <strong>Selamat datang, {{ auth()->user()->name }}</strong>
            {{ auth()->user()->jabatan?->label() ?? auth()->user()->getRoleNames()->first() }}
            @if (auth()->user()->unit) · {{ auth()->user()->unit->nama }}@endif
        </div>
    </div>
    @include('layouts.partials.menu')
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<div class="main">
    <header class="topbar d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-lg-none" aria-label="Buka navigasi" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <h1 class="institution-title">{{ config('app.name', 'Sistem Penjaminan Mutu Internal') }}</h1>
        </div>
        <div class="topbar-actions d-flex align-items-center gap-2">
            <span class="small text-muted d-none d-md-inline">{{ auth()->user()->name }}</span>
            @if (auth()->user()->hasRole('admin_spmi'))
                <a href="{{ route('cycles.index') }}" class="btn btn-link px-2"><i class="bi bi-gear-fill me-1"></i><span class="d-none d-sm-inline">Pengaturan</span></a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-link px-2"><i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-sm-inline">Keluar</span></button>
            </form>
        </div>
    </header>
    <div class="breadcrumb-bar">
        <a href="{{ route('dashboard') }}">Beranda</a>
        <span class="mx-2">/</span>
        <span>{{ trim($__env->yieldContent('title')) ?: 'Dashboard' }}</span>
        @if ($activeCycle)
            <span class="float-end text-muted">Siklus {{ $activeCycle->tahun }} · {{ $activeCycle->tahap_aktif->label() }}</span>
        @endif
    </div>

    <main class="content">
        @include('partials.alerts')
        @yield('content')
    </main>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarBackdrop').classList.toggle('show');
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('sidebarBackdrop').classList.remove('show');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
