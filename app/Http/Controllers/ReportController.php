<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\Cycle;
use App\Services\DashboardService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $cycles = Cycle::orderByDesc('tahun')->get();

        return view('reports.index', [
            'cycles' => $cycles,
            'currentCycleId' => Cycle::current()?->id ?? $cycles->first()?->id,
            'units' => $reports->selectableUnits($request->user()),
            'types' => ReportService::TYPES,
            'lockUnit' => ! $request->user()->hasAnyRole(['admin_spmi', 'pimpinan']),
        ]);
    }

    public function download(Request $request, ReportService $reports, DashboardService $dashboard)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ReportService::TYPES))],
            'format' => ['required', 'in:pdf,xlsx'],
            'cycle_id' => ['required', 'exists:cycles,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
        ]);

        $cycle = Cycle::findOrFail($data['cycle_id']);
        $units = $reports->resolveUnits($request->user(), $cycle, $data['unit_id'] ?? null);
        $unitLabel = $units === null ? 'Semua unit' : \App\Models\Unit::whereIn('id', $units)->pluck('nama')->implode(', ');
        $slug = Str::slug("{$data['type']}-{$cycle->tahun}");

        // Ringkasan siklus: PDF, satu unit atau seluruh institusi.
        if ($data['type'] === 'summary') {
            abort_if($units !== null && count($units) !== 1, 422, 'Pilih satu unit untuk ringkasan siklus.');

            return Pdf::loadView('reports.summary', [
                'cycle' => $cycle,
                'unitLabel' => $unitLabel,
                'data' => $dashboard->build($cycle, $units[0] ?? null),
            ])->setPaper('a4')->download("{$slug}.pdf");
        }

        $set = $reports->dataset($data['type'], $cycle, $units);

        if ($data['format'] === 'xlsx') {
            return Excel::download(new ArrayExport($set['headings'], $set['rows'], $set['title']), "{$slug}.xlsx");
        }

        return Pdf::loadView('reports.pdf', [
            'title' => $set['title'],
            'headings' => $set['headings'],
            'rows' => $set['rows'],
            'cycle' => $cycle,
            'unitLabel' => $unitLabel,
        ])->setPaper('a4', 'landscape')->download("{$slug}.pdf");
    }
}
