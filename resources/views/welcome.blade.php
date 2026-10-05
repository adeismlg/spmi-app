<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SPMI') }} — Sistem Penjaminan Mutu Internal</title>
    <meta name="description" content="Kelola siklus PPEPP penjaminan mutu perguruan tinggi: standar, evaluasi diri per semester, audit mutu internal, tindak lanjut, dan laporan dalam satu aplikasi.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1d2140; --muted: #5d6385; --line: #e6e8f5; --bg: #f7f8ff; --card: #fff;
            --brand: #5b5ef4; --brand-2: #8b5cf6; --accent: #14b8a6; --warn: #f59e0b; --rose: #f43f5e;
            --r: 18px; --shadow: 0 10px 30px rgba(60, 64, 160, .10);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; color: var(--ink); background: var(--bg); line-height: 1.6; }
        a { color: inherit; text-decoration: none; }
        .wrap { width: min(1140px, 92%); margin-inline: auto; }

        /* Navbar */
        .nav { position: sticky; top: 0; z-index: 50; backdrop-filter: blur(12px); background: rgba(247, 248, 255, .85); border-bottom: 1px solid var(--line); }
        .nav .wrap { display: flex; align-items: center; justify-content: space-between; height: 68px; }
        .logo { display: flex; align-items: center; gap: .6rem; font-weight: 800; font-size: 1.2rem; }
        .logo i { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 11px; color: #fff; background: linear-gradient(135deg, var(--brand), var(--brand-2)); }
        .menu { display: flex; gap: 1.6rem; font-weight: 600; color: var(--muted); font-size: .95rem; }
        .menu a:hover { color: var(--brand); }
        .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1.3rem; border-radius: 12px; font-weight: 700; font-size: .95rem; border: 1.5px solid transparent; cursor: pointer; transition: .2s; }
        .btn-primary { color: #fff; background: linear-gradient(135deg, var(--brand), var(--brand-2)); box-shadow: 0 8px 20px rgba(91, 94, 244, .35); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(91, 94, 244, .45); }
        .btn-ghost { color: var(--brand); background: #fff; border-color: var(--line); }
        .btn-ghost:hover { border-color: var(--brand); }

        /* Hero */
        .hero { position: relative; overflow: hidden; padding: 84px 0 70px; }
        .hero::before, .hero::after { content: ''; position: absolute; border-radius: 50%; filter: blur(80px); z-index: 0; }
        .hero::before { width: 460px; height: 460px; background: rgba(91, 94, 244, .22); top: -140px; right: -80px; }
        .hero::after { width: 380px; height: 380px; background: rgba(20, 184, 166, .18); bottom: -160px; left: -100px; }
        .hero .wrap { position: relative; z-index: 1; display: grid; grid-template-columns: 1.05fr .95fr; gap: 3rem; align-items: center; }
        .pill { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .9rem; border-radius: 99px; background: #fff; border: 1px solid var(--line); font-size: .82rem; font-weight: 700; color: var(--brand); }
        h1 { font-size: clamp(2.1rem, 4.6vw, 3.4rem); line-height: 1.12; margin: 1rem 0; font-weight: 800; letter-spacing: -.02em; }
        h1 span { background: linear-gradient(135deg, var(--brand), var(--brand-2) 60%, var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .lead { font-size: 1.1rem; color: var(--muted); max-width: 34rem; }
        .cta { display: flex; flex-wrap: wrap; gap: .8rem; margin: 1.8rem 0 1.4rem; }
        .trust { display: flex; flex-wrap: wrap; gap: 1.2rem; font-size: .88rem; color: var(--muted); font-weight: 600; }
        .trust i { color: var(--accent); margin-right: .3rem; }

        /* Mock dashboard */
        .mock { background: var(--card); border: 1px solid var(--line); border-radius: 22px; box-shadow: var(--shadow); padding: 1.1rem; transform: rotate(1.2deg); }
        .mock-bar { display: flex; gap: .4rem; margin-bottom: .9rem; }
        .mock-bar i { width: 10px; height: 10px; border-radius: 50%; background: #e3e5f3; }
        .mock-bar i:nth-child(1) { background: #fda4af; } .mock-bar i:nth-child(2) { background: #fde68a; } .mock-bar i:nth-child(3) { background: #99f6e4; }
        .mock-stages { display: flex; gap: .3rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .mock-stages span { font-size: .68rem; font-weight: 700; padding: .25rem .6rem; border-radius: 99px; background: #eef0ff; color: var(--muted); }
        .mock-stages .done { background: #d1fae5; color: #047857; } .mock-stages .on { background: var(--brand); color: #fff; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; margin-bottom: 1rem; }
        .kpi { border: 1px solid var(--line); border-radius: 12px; padding: .6rem .7rem; }
        .kpi b { display: block; font-size: 1.25rem; } .kpi small { color: var(--muted); font-size: .7rem; }
        .bars { display: grid; gap: .55rem; }
        .bar-row { display: grid; grid-template-columns: 74px 1fr; align-items: center; gap: .6rem; font-size: .72rem; font-weight: 600; color: var(--muted); }
        .track { height: 9px; border-radius: 9px; background: #eef0ff; overflow: hidden; }
        .track i { display: block; height: 100%; border-radius: 9px; background: linear-gradient(90deg, var(--brand), var(--brand-2)); }
        .track i.t { background: linear-gradient(90deg, var(--accent), #5eead4); }
        .note { margin-top: .8rem; font-size: .68rem; color: #9aa0c3; text-align: right; }

        /* Sections */
        section { padding: 76px 0; }
        .head { text-align: center; max-width: 40rem; margin: 0 auto 3rem; }
        .eyebrow { font-size: .8rem; letter-spacing: .12em; text-transform: uppercase; font-weight: 800; color: var(--brand); }
        h2 { font-size: clamp(1.7rem, 3.2vw, 2.4rem); line-height: 1.2; margin: .4rem 0 .8rem; font-weight: 800; letter-spacing: -.015em; }
        .head p { color: var(--muted); margin: 0; }

        /* PPEPP */
        .cycle { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; position: relative; }
        .cycle::before { content: ''; position: absolute; top: 34px; left: 8%; right: 8%; height: 3px; background: repeating-linear-gradient(90deg, var(--line) 0 10px, transparent 10px 18px); z-index: 0; }
        .step { position: relative; z-index: 1; background: var(--card); border: 1px solid var(--line); border-radius: var(--r); padding: 1.2rem 1rem 1.3rem; text-align: center; transition: .25s; }
        .step:hover { transform: translateY(-6px); box-shadow: var(--shadow); }
        .step .no { display: grid; place-items: center; width: 52px; height: 52px; margin: -2.4rem auto .8rem; border-radius: 16px; color: #fff; font-weight: 800; font-size: 1.3rem; box-shadow: 0 8px 18px rgba(0, 0, 0, .12); }
        .step:nth-child(1) .no { background: linear-gradient(135deg, #5b5ef4, #7c7ff9); }
        .step:nth-child(2) .no { background: linear-gradient(135deg, #14b8a6, #2dd4bf); }
        .step:nth-child(3) .no { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .step:nth-child(4) .no { background: linear-gradient(135deg, #f43f5e, #fb7185); }
        .step:nth-child(5) .no { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .step h3 { margin: 0 0 .3rem; font-size: 1.05rem; } .step p { margin: 0; font-size: .86rem; color: var(--muted); }
        .loop { text-align: center; margin-top: 1.6rem; color: var(--muted); font-size: .9rem; font-weight: 600; }
        .loop i { color: var(--brand); }

        /* Features */
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.2rem; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: var(--r); padding: 1.5rem; transition: .25s; }
        .card:hover { transform: translateY(-5px); box-shadow: var(--shadow); border-color: #d6d9f7; }
        .ico { display: grid; place-items: center; width: 48px; height: 48px; border-radius: 14px; font-size: 1.35rem; margin-bottom: 1rem; }
        .c1 { background: #eef0ff; color: var(--brand); } .c2 { background: #ccfbf1; color: #0f766e; } .c3 { background: #fef3c7; color: #b45309; }
        .c4 { background: #ffe4e6; color: #be123c; } .c5 { background: #ede9fe; color: #6d28d9; } .c6 { background: #dbeafe; color: #1d4ed8; }
        .card h3 { margin: 0 0 .4rem; font-size: 1.08rem; } .card p { margin: 0; color: var(--muted); font-size: .92rem; }

        /* Alur peran */
        .band { background: linear-gradient(135deg, #2b2f6e, #4338ca 55%, #6d28d9); color: #fff; border-radius: 28px; padding: 3rem; position: relative; overflow: hidden; }
        .band::after { content: ''; position: absolute; width: 360px; height: 360px; right: -120px; top: -140px; border-radius: 50%; background: rgba(255, 255, 255, .08); }
        .band h2 { color: #fff; } .band .eyebrow { color: #c7d2fe; }
        .roles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 2rem; position: relative; z-index: 1; }
        .role { background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .18); border-radius: 16px; padding: 1.1rem; }
        .role i { font-size: 1.4rem; color: #c7d2fe; } .role h3 { margin: .5rem 0 .25rem; font-size: 1rem; } .role p { margin: 0; font-size: .85rem; color: #dbe0ff; }
        .chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.6rem; position: relative; z-index: 1; }
        .chip { font-size: .8rem; font-weight: 700; padding: .35rem .8rem; border-radius: 99px; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .2); }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .stat { text-align: center; background: var(--card); border: 1px solid var(--line); border-radius: var(--r); padding: 1.6rem 1rem; }
        .stat b { display: block; font-size: 2.3rem; line-height: 1.1; background: linear-gradient(135deg, var(--brand), var(--brand-2)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .stat span { color: var(--muted); font-size: .88rem; font-weight: 600; }

        /* CTA & footer */
        .final { text-align: center; background: var(--card); border: 1px solid var(--line); border-radius: 28px; padding: 3.4rem 1.5rem; box-shadow: var(--shadow); }
        .final p { color: var(--muted); max-width: 32rem; margin: 0 auto 1.6rem; }
        footer { padding: 2.4rem 0 3rem; color: var(--muted); font-size: .88rem; }
        footer .wrap { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--line); padding-top: 1.6rem; }

        @media (max-width: 960px) {
            .hero .wrap { grid-template-columns: 1fr; } .mock { transform: none; max-width: 520px; }
            .cycle { grid-template-columns: 1fr 1fr; row-gap: 2.6rem; } .cycle::before { display: none; }
            .grid { grid-template-columns: 1fr 1fr; } .roles, .stats { grid-template-columns: 1fr 1fr; } .menu { display: none; }
        }
        @media (max-width: 600px) { .grid, .roles, .stats, .cycle { grid-template-columns: 1fr; } .band { padding: 2rem 1.3rem; } section { padding: 56px 0; } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; scroll-behavior: auto !important; } }
    </style>
</head>
<body>

<header class="nav">
    <div class="wrap">
        <a href="#" class="logo"><i class="bi bi-shield-check"></i> {{ config('app.name', 'SPMI') }}</a>
        <nav class="menu">
            <a href="#siklus">Siklus PPEPP</a>
            <a href="#fitur">Fitur</a>
            <a href="#peran">Peran &amp; PIC</a>
        </nav>
        <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Masuk</a>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero">
        <div class="wrap">
            <div>
                <span class="pill"><i class="bi bi-arrow-repeat"></i> Siklus PPEPP terintegrasi</span>
                <h1>Kelola <span>mutu kampus</span> dari standar hingga peningkatan.</h1>
                <p class="lead">Satu aplikasi untuk menetapkan standar, mengisi evaluasi diri per semester, menjalankan audit mutu internal, menindaklanjuti temuan, dan melaporkannya.</p>
                <div class="cta">
                    <a href="{{ route('login') }}" class="btn btn-primary">Mulai sekarang <i class="bi bi-arrow-right"></i></a>
                    <a href="#siklus" class="btn btn-ghost"><i class="bi bi-play-circle"></i> Lihat alur kerja</a>
                </div>
                <div class="trust">
                    <span><i class="bi bi-check-circle-fill"></i>Berbasis PPEPP</span>
                    <span><i class="bi bi-check-circle-fill"></i>Evaluasi per semester</span>
                    <span><i class="bi bi-check-circle-fill"></i>Pengingat via email</span>
                </div>
            </div>

            <div class="mock" aria-hidden="true">
                <div class="mock-bar"><i></i><i></i><i></i></div>
                <div class="mock-stages">
                    <span class="done">P · Penetapan</span><span class="done">P · Pelaksanaan</span><span class="on">E · Evaluasi</span><span>P · Pengendalian</span><span>P · Peningkatan</span>
                </div>
                <div class="kpis">
                    <div class="kpi"><b>24</b><small>Standar</small></div>
                    <div class="kpi"><b>2</b><small>Semester / tahun</small></div>
                    <div class="kpi"><b>5</b><small>Tahap siklus</small></div>
                </div>
                <div class="bars">
                    <div class="bar-row">Target <div class="track"><i class="t" style="width:100%"></i></div></div>
                    <div class="bar-row">Evaluasi diri <div class="track"><i style="width:82%"></i></div></div>
                    <div class="bar-row">Audit <div class="track"><i style="width:74%"></i></div></div>
                </div>
                <div class="note">Ilustrasi tampilan dashboard</div>
            </div>
        </div>
    </section>

    <!-- SIKLUS -->
    <section id="siklus">
        <div class="wrap">
            <div class="head">
                <div class="eyebrow">Alur kerja</div>
                <h2>Lima tahap, satu siklus berkelanjutan</h2>
                <p>Setiap tahap dibuka bergantian. Isian terkunci otomatis bila bukan gilirannya, sehingga proses mutu tertib dan dapat ditelusuri.</p>
            </div>
            <div class="cycle">
                <div class="step"><div class="no">P</div><h3>Penetapan</h3><p>Standar, indikator, target, dan dokumen mutu ditetapkan.</p></div>
                <div class="step"><div class="no">P</div><h3>Pelaksanaan</h3><p>Unit mengisi evaluasi diri per semester beserta bukti dukung.</p></div>
                <div class="step"><div class="no">E</div><h3>Evaluasi</h3><p>Auditor mengisi daftar tilik dan mencatat temuan AMI.</p></div>
                <div class="step"><div class="no">P</div><h3>Pengendalian</h3><p>Rencana tindak lanjut disusun, dipantau, dan diverifikasi.</p></div>
                <div class="step"><div class="no">P</div><h3>Peningkatan</h3><p>Hasil tinjauan manajemen menjadi masukan siklus berikutnya.</p></div>
            </div>
            <div class="loop"><i class="bi bi-arrow-repeat"></i> Hasil peningkatan kembali menjadi dasar penetapan standar tahun berikutnya</div>
        </div>
    </section>

    <!-- FITUR -->
    <section id="fitur">
        <div class="wrap">
            <div class="head">
                <div class="eyebrow">Fitur</div>
                <h2>Semua kebutuhan penjaminan mutu, dalam satu tempat</h2>
                <p>Dirancang mengikuti dokumen standar kampus, dari pernyataan berformat ABCD sampai target tahunan.</p>
            </div>
            <div class="grid">
                <div class="card"><div class="ico c1"><i class="bi bi-journal-check"></i></div><h3>Standar &amp; Indikator</h3><p>Pernyataan standar, PIC baku, periode evaluasi, referensi, serta baseline dan target 2025–2030.</p></div>
                <div class="card"><div class="ico c2"><i class="bi bi-diagram-3"></i></div><h3>Penugasan Tepat Sasaran</h3><p>Standar hanya ditugaskan ke unit, jurusan, atau prodi (sesuai jenjang) yang memang relevan.</p></div>
                <div class="card"><div class="ico c3"><i class="bi bi-clipboard2-check"></i></div><h3>Evaluasi Diri per Semester</h3><p>Isian menyesuaikan jenis indikator: persen, angka, ada/tidak, rupiah. Skor dihitung otomatis.</p></div>
                <div class="card"><div class="ico c4"><i class="bi bi-search"></i></div><h3>Audit Mutu Internal</h3><p>Plotting auditor, daftar tilik, dan temuan Observasi/KTS yang tercatat otomatis.</p></div>
                <div class="card"><div class="ico c5"><i class="bi bi-envelope-check"></i></div><h3>Tindak Lanjut &amp; Pengingat</h3><p>RTL dengan tenggat, verifikasi auditor, dan pengingat email H-7, H-3, H-1, hari-H.</p></div>
                <div class="card"><div class="ico c6"><i class="bi bi-graph-up-arrow"></i></div><h3>Dashboard &amp; Laporan</h3><p>Radar target vs evaluasi diri vs audit, tren tahunan, dan ekspor PDF atau Excel.</p></div>
            </div>
        </div>
    </section>

    <!-- PERAN -->
    <section id="peran">
        <div class="wrap">
            <div class="band">
                <div class="eyebrow">Peran &amp; PIC</div>
                <h2>Setiap orang melihat yang menjadi tanggung jawabnya</h2>
                <p style="color:#dbe0ff;max-width:38rem;margin:0">Akses dibatasi menurut peran dan jabatan, sehingga pengisi hanya menemui indikator miliknya.</p>
                <div class="roles">
                    <div class="role"><i class="bi bi-gear-wide-connected"></i><h3>Admin SPMI</h3><p>Mengelola standar, siklus, penugasan, dan plotting auditor.</p></div>
                    <div class="role"><i class="bi bi-person-lines-fill"></i><h3>PIC / Auditee</h3><p>Mengisi evaluasi diri dan menyusun tindak lanjut.</p></div>
                    <div class="role"><i class="bi bi-binoculars"></i><h3>Auditor</h3><p>Mengisi daftar tilik dan memverifikasi tindak lanjut.</p></div>
                    <div class="role"><i class="bi bi-bar-chart-line"></i><h3>Pimpinan</h3><p>Memantau dashboard dan hasil tinjauan manajemen.</p></div>
                </div>
                <div class="chips">
                    <span class="chip">Ketua Unit</span><span class="chip">Ketua Jurusan</span><span class="chip">Koordinator Program Studi</span>
                    <span class="chip">Wakil Direktur I</span><span class="chip">Wakil Direktur II</span><span class="chip">Wakil Direktur III</span><span class="chip">Direktur</span>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS + CTA -->
    <section>
        <div class="wrap">
            <div class="stats">
                <div class="stat"><b>24</b><span>Standar mutu</span></div>
                <div class="stat"><b>3</b><span>Kelompok: Pembelajaran, Penelitian, PkM</span></div>
                <div class="stat"><b>2×</b><span>Evaluasi per tahun</span></div>
                <div class="stat"><b>7</b><span>Jabatan PIC baku</span></div>
            </div>

            <div class="final" style="margin-top:2.6rem">
                <h2>Siap menjalankan siklus mutu yang lebih tertib?</h2>
                <p>Masuk dengan akun yang diberikan Admin SPMI untuk mulai mengisi atau memantau.</p>
                <a href="{{ route('login') }}" class="btn btn-primary">Masuk ke aplikasi <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap">
        <span>© {{ date('Y') }} {{ config('app.name', 'SPMI') }} · Sistem Penjaminan Mutu Internal</span>
        <span>Siklus Penetapan · Pelaksanaan · Evaluasi · Pengendalian · Peningkatan</span>
    </div>
</footer>

</body>
</html>
