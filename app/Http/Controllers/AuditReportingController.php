<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Unit;
use App\Services\AuditReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditReportingController extends Controller
{
    public function index(Request $request, AuditReportingService $auditReporting): View
    {
        $user = $request->user();
        $cycles = Cycle::with('standardEdition')->orderByDesc('tahun')->get();
        $cycle = $request->filled('cycle_id') ? $cycles->firstWhere('id', $request->integer('cycle_id')) : (Cycle::current() ?? $cycles->first());

        // Admin/pimpinan/auditor dapat memfilter unit; auditee dikunci ke unitnya
        $canFilterUnit = $user->hasAnyRole(['admin_spmi', 'pimpinan', 'auditor']);
        $unitId = $canFilterUnit ? ($request->integer('unit_id') ?: null) : $user->unit_id;

        // Filter unit berdasarkan tipe (hanya prodi dan lab untuk keperluan filtering)
        $filterableUnits = Unit::whereIn('tipe', ['prodi', 'laboratorium'])
            ->orderBy('nama')
            ->get();

        $auditData = $auditReporting->auditSummaryByType($cycle, $unitId);

        return view('audits.reporting', [
            'cycles' => $cycles,
            'cycle' => $cycle,
            'units' => $canFilterUnit ? $filterableUnits : collect(),
            'unitId' => $unitId,
            'audits' => $auditData['academic'],
            'labs' => $auditData['laboratory'],
            'summary' => $auditData['summary'],
        ]);
    }
}
