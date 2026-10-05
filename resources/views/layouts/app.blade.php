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
        :root { --bs-primary:#16866f; --bs-primary-rgb:22,134,111; --bs-link-color:#16866f; --bs-body-bg:#f3f5f4; --sidebar-w:220px; --sidebar-bg:#26343b; --sidebar-muted:#b3c0c5; --spmi-accent:#16866f; --spmi-accent-soft:#e3f1ec; }
        body { font-family:'Public Sans',system-ui,sans-serif; color:#5d6670; background:#f3f4f6; }
        .btn-primary { --bs-btn-bg:#16866f; --bs-btn-border-color:#16866f; --bs-btn-hover-bg:#116d5b; --bs-btn-hover-border-color:#116d5b; --bs-btn-active-bg:#0e5b4c; --bs-btn-active-border-color:#0e5b4c; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:var(--sidebar-w); background:var(--sidebar-bg); overflow-y:auto; z-index:1030; color:#fff; scrollbar-width:thin; scrollbar-color:#56666d transparent; }
        .brand { min-height:61px; display:flex; align-items:center; gap:.7rem; padding:.65rem 1rem; font-size:1.35rem; font-weight:700; color:#f3faf7; background:linear-gradient(110deg,#31483f 0%,#1d705d 100%); letter-spacing:-.04em; }
        .brand-mark { width:34px; height:34px; display:grid; place-items:center; border-radius:50%; color:#fff; background:#15866f; box-shadow:inset 0 0 0 3px rgba(255,255,255,.24); font-size:1.1rem; }
        .sidebar-meta { padding:.8rem .9rem .9rem; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-date { display:block; text-align:center; padding:.22rem .4rem; margin-bottom:.75rem; color:#315146; background:#e9f1ed; font-size:.72rem; font-weight:600; }
        .sidebar-user { font-size:.72rem; line-height:1.45; color:#fff; }
        .sidebar-user strong { display:block; font-size:.74rem; margin-bottom:.2rem; }
        .menu { padding:0 0 1.5rem; }
        .menu .nav-link { color:var(--sidebar-muted); border-radius:0; border-left:4px solid transparent; padding:.62rem .9rem; display:flex; align-items:center; gap:.65rem; margin:0; font-size:.79rem; font-weight:600; }
        .menu .nav-link:hover { background:rgba(255,255,255,.06); color:#fff; }
        .menu .nav-link.active { background:#34463f; border-left-color:#45c49a; color:#fff; }
        .menu .nav-link i { width:1rem; text-align:center; font-size:.9rem; }
        .menu-header { font-size:.68rem; text-transform:uppercase; letter-spacing:.08em; color:#8192a2; padding:1.05rem .9rem .35rem; }
        .main { margin-left:var(--sidebar-w); min-height:100vh; }
        .topbar { min-height:62px; background:#fff; padding:.55rem 1.25rem; position:sticky; top:0; z-index:1020; border-bottom:1px solid #e3e5e8; }
        .institution-title { margin:0; color:#171a1d; font-size:1.3rem; font-weight:700; letter-spacing:-.035em; }
        .topbar-actions { color:#24282d; }
        .topbar-actions .btn-link { color:inherit; text-decoration:none; font-size:.82rem; font-weight:600; }
        .breadcrumb-bar { padding:.4rem 1.25rem .7rem; color:#646b72; font-size:.78rem; }
        .breadcrumb-bar a { color:#646b72; text-decoration:none; }
        .breadcrumb-bar a:hover { color:var(--spmi-accent); }
        .content { padding:1rem 1.25rem 2rem; }
        .card { border:1px solid #e2e4e7; box-shadow:none; border-radius:.15rem; background:#fff; }
        .card-header { background:#fff; border-bottom:1px solid #e2e4e7; color:#30363b; padding:.8rem .95rem; }
        .table > :not(caption) > * > * { padding:.58rem .65rem; }
        .table { color:#646b72; }
        .table thead th { font-size:.76rem; font-weight:700; color:#646b72; background:#f5f5f5; white-space:normal; border-color:#dedfe1; }
        .table tbody td { border-color:#e1e2e4; font-size:.82rem; }
        .table-hover tbody tr:hover > * { --bs-table-bg-state:#f8fafb; }
        .form-control, .form-select, .input-group-text { border-radius:.15rem; border-color:#d6dbe0; }
        .btn { border-radius:.15rem; }
        .alert { border-radius:.15rem; }
        .stage-pill { font-size:.72rem; padding:.3rem .65rem; border-radius:2rem; }
        .sidebar-backdrop { display:none; position:fixed; inset:0; z-index:1025; background:rgba(16,27,37,.52); }
        .sidebar-backdrop.show { display:block; }
        @media (max-width:991px) {
            .sidebar { transform:translateX(-100%); transition:transform .2s ease; }
            .sidebar.show { transform:none; }
            .main { margin-left:0; }
            .content { padding:.9rem .85rem 1.5rem; }
            .topbar { padding:.55rem .85rem; }
            .breadcrumb-bar { padding:.35rem .85rem .6rem; }
            .institution-title { font-size:1.05rem; }
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
