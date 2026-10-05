<?php

namespace App\Http\Controllers;

use App\Enums\UnitType;
use App\Models\Statement;
use App\Models\Unit;
use App\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Mengatur unit/prodi mana saja yang menerima satu pernyataan standar. */
class StatementAssignmentController extends Controller
{
    public function edit(Statement $statement): View
    {
        $statement->load('standard', 'assignments');

        return view('statements.assignments', [
            'statement' => $statement,
            'groups' => Unit::with('parent')->orderBy('nama')->get()->groupBy(fn ($u) => $u->tipe->value),
            'types' => collect(UnitType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(),
            'selected' => $statement->assignments->pluck('unit_id')->unique()->all(),
            'manual' => $statement->assignments->contains('sumber', 'manual'),
        ]);
    }

    public function update(Request $request, Statement $statement, AssignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'units' => 'nullable|array',
            'units.*' => 'exists:units,id',
        ]);

        $service->setManual($statement, $data['units'] ?? []);

        return redirect()->route('statements.index')->with('success', 'Penugasan pernyataan '.($statement->nomor ?? '').' disimpan.');
    }

    public function reset(Statement $statement, AssignmentService $service): RedirectResponse
    {
        $n = $service->reset($statement);

        return back()->with('success', "Penugasan dikembalikan ke otomatis ({$n} unit).");
    }
}
