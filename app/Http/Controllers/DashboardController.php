<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\Unit;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $service): View
    {
        $user = $request->user();
        $cycles = Cycle::orderByDesc('tahun')->get();
        $cycle = $request->filled('cycle_id') ? $cycles->firstWhere('id', $request->integer('cycle_id')) : (Cycle::current() ?? $cycles->first());

        // Admin/pimpinan/auditor boleh memfilter unit; auditee dikunci ke unitnya.
        $canFilterUnit = $user->hasAnyRole(['admin_spmi', 'pimpinan', 'auditor']);
        $unitId = $canFilterUnit ? ($request->integer('unit_id') ?: null) : $user->unit_id;

        return view('dashboard', [
            'cycles' => $cycles,
            'cycle' => $cycle,
            'units' => $canFilterUnit ? Unit::orderBy('nama')->get() : collect(),
            'unitId' => $unitId,
            'data' => $service->build($cycle, $unitId),
        ]);
    }
}
