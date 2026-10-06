<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #392579; color: #fff; font-size: 8.5px; text-align: left; padding: 5px 4px; }
        td { border-bottom: 1px solid #ddd; padding: 4px; vertical-align: top; }
        tr:nth-child(even) td { background: #f7f7fb; }
        .empty { text-align: center; color: #888; padding: 20px; }
        .foot { margin-top: 10px; color: #888; font-size: 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Siklus {{ $cycle->tahun }} · {{ $unitLabel }} · Dicetak {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
        <tr><th style="width:22px">#</th>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse ($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                @foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach
            </tr>
        @empty
            <tr><td class="empty" colspan="{{ count($headings) + 1 }}">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="foot">Total {{ count($rows) }} baris · SPMI</div>
</body>
</html>
