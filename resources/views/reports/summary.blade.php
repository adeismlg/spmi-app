<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Siklus {{ $cycle->tahun }}</title>
    <style>
        @page { margin: 34px 38px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 17px; margin: 0 0 3px; }
        h2 { font-size: 12px; margin: 18px 0 6px; color: #5457c9; }
        .meta { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #696cff; color: #fff; text-align: left; padding: 5px; font-size: 9px; }
        td { border-bottom: 1px solid #ddd; padding: 5px; vertical-align: middle; }
        .bar { background: #e9e9f3; height: 8px; width: 100%; }
        .bar span { display: block; height: 8px; }
        .kpi td { border: 1px solid #ddd; text-align: center; padding: 8px 4px; width: 20%; }
        .kpi .v { font-size: 16px; font-weight: bold; }
        .kpi .l { color: #666; font-size: 8px; }
    </style>
</head>
<body>
@php $s = $data['stats']; @endphp
    <h1>Ringkasan Siklus SPMI {{ $cycle->tahun }}</h1>
    <div class="meta">{{ $unitLabel }} · Tahap aktif: {{ $cycle->tahap_aktif->label() }} · Dicetak {{ now()->format('d/m/Y H:i') }}</div>

    <table class="kpi"><tr>
        <td><div class="v">{{ $s['unit_submitted'] ?? 0 }}</div><div class="l">Unit mengajukan evaluasi diri</div></td>
        <td><div class="v">{{ ($s['audits_done'] ?? 0) }} / {{ ($s['audits_total'] ?? 0) }}</div><div class="l">Audit selesai</div></td>
        <td><div class="v">{{ $s['findings_total'] ?? 0 }}</div><div class="l">Total temuan</div></td>
        <td><div class="v">{{ $s['actions_done_pct'] ?? 0 }}%</div><div class="l">RTL selesai</div></td>
        <td><div class="v">{{ $s['actions_overdue'] ?? 0 }}</div><div class="l">RTL terlambat</div></td>
    </tr></table>

    <h2>Capaian per Standar (skala 0–100)</h2>
    @if ($data['radar'])
        <table>
            <thead><tr><th style="width:34%">Standar</th><th style="width:33%">Evaluasi Diri</th><th>Audit</th></tr></thead>
            <tbody>
            @foreach ($data['standards'] as $i => $std)
                @php $self = $data['radar']['self'][$i]; $aud = $data['radar']['audit'][$i]; @endphp
                <tr>
                    <td><strong>{{ $std->kode }}</strong> {{ $std->nama }}</td>
                    <td>
                        <div class="bar"><span style="width: {{ $self }}%; background:#696cff"></span></div>
                        {{ number_format($self, 1) }}
                    </td>
                    <td>
                        <div class="bar"><span style="width: {{ $aud }}%; background:#ff3e1d"></span></div>
                        {{ number_format($aud, 1) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Temuan per Kategori</h2>
    <table>
        <thead><tr><th>Kategori</th><th style="width:80px">Jumlah</th></tr></thead>
        <tbody>
        @foreach (\App\Enums\Conformity::cases() as $c)
            @continue(! $c->isFinding())
            <tr><td>{{ $c->label() }}</td><td>{{ $s['findings_by_kategori'][$c->value] ?? 0 }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <h2>Status Rencana Tindak Lanjut</h2>
    <table>
        <thead><tr><th>Status</th><th style="width:80px">Jumlah</th></tr></thead>
        <tbody>
        @foreach (\App\Enums\ActionStatus::cases() as $a)
            <tr><td>{{ $a->label() }}</td><td>{{ $s['actions_by_status'][$a->value] ?? 0 }}</td></tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
