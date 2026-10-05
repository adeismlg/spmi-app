<?php

use App\Http\Controllers\AuditChecklistController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CorrectiveActionController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceReviewController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\ManagementReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SelfEvaluationController;
use App\Http\Controllers\ReopenRequestController;
use App\Http\Controllers\SemesterWindowController;
use App\Http\Controllers\StandardController;
use App\Http\Controllers\StatementAssignmentController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    /* ---------- Dapat dibaca semua peran (data dibatasi di controller) ---------- */
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('documents/{id}/download', [DocumentController::class, 'download'])->whereNumber('id')->name('documents.download');
    Route::get('findings', [FindingController::class, 'index'])->name('findings.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/download', [ReportController::class, 'download'])->name('reports.download');
    Route::get('corrective-actions', [CorrectiveActionController::class, 'index'])->name('corrective-actions.index');
    Route::get('self-evaluations/{selfEvaluation}', [SelfEvaluationController::class, 'show'])
        ->whereNumber('selfEvaluation')->name('self-evaluations.show');

    /* ---------- Admin SPMI: master data & pengaturan siklus ---------- */
    Route::middleware('role:admin_spmi')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::resource('units', UnitController::class)->except('show');
        Route::resource('standards', StandardController::class)->except('show');
        Route::get('statements/{statement}/assignments', [StatementAssignmentController::class, 'edit'])->name('statements.assignments.edit');
        Route::put('statements/{statement}/assignments', [StatementAssignmentController::class, 'update'])->name('statements.assignments.update');
        Route::post('statements/{statement}/assignments/reset', [StatementAssignmentController::class, 'reset'])->name('statements.assignments.reset');
        Route::resource('statements', StatementController::class)->except('show');
        Route::resource('indicators', IndicatorController::class)->except('show');
        Route::resource('activities', ActivityController::class)->except('show');
        Route::resource('semester-windows', SemesterWindowController::class)->except('show');
        Route::resource('cycles', CycleController::class)->except('show');
        Route::post('cycles/{cycle}/advance', [CycleController::class, 'advance'])->name('cycles.advance');

        // Tahap PENETAPAN
        Route::resource('documents', DocumentController::class)->except(['index', 'show']);

        // Plotting auditor & jadwal AMI, RTM
        Route::resource('audits', AuditController::class)->except(['index', 'show']);
        Route::resource('management-reviews', ManagementReviewController::class)->except(['index', 'show']);
    });

    Route::middleware('role:admin_spmi|pimpinan')->group(function () {
        Route::get('management-reviews', [ManagementReviewController::class, 'index'])->name('management-reviews.index');
    });

    /* ---------- PELAKSANAAN: evaluasi diri & bukti dukung (auditee) ---------- */
    Route::middleware('role:admin_spmi|auditee')->group(function () {
        Route::get('self-evaluations', [SelfEvaluationController::class, 'index'])->name('self-evaluations.index');

        Route::put('self-evaluations', [SelfEvaluationController::class, 'update'])->name('self-evaluations.update');
        Route::post('self-evaluations/{selfEvaluation}/activities/{activity}/evidences', [EvidenceController::class, 'store'])
            ->name('activities.evidences.store');
        Route::post('self-evaluations/{selfEvaluation}/reopen-requests', [ReopenRequestController::class, 'store'])
            ->name('reopen-requests.store');
        Route::delete('evidences/{evidence}', [EvidenceController::class, 'destroy'])->name('evidences.destroy');
    });

    /* ---------- EVALUASI: AMI ---------- */
    Route::middleware('role:admin_spmi|auditor|pimpinan')->group(function () {
        Route::get('audits', [AuditController::class, 'index'])->name('audits.index');
        Route::get('audits/{id}', [AuditController::class, 'show'])->whereNumber('id')->name('audits.show');
    });

    Route::middleware(['role:admin_spmi|auditor', 'stage:evaluasi'])->group(function () {
        Route::post('audits/{audit}/checklist/generate', [AuditChecklistController::class, 'generate'])->name('audits.checklist.generate');
        Route::put('audits/{audit}/checklist', [AuditChecklistController::class, 'update'])->name('audits.checklist.update');
        Route::put('evidences/{evidence}/review', [EvidenceReviewController::class, 'update'])->name('evidences.review');
    });

    Route::get('evidences/{evidence}/download', [EvidenceController::class, 'download'])->name('evidences.download');
    Route::get('reopen-requests', [ReopenRequestController::class, 'index'])
        ->middleware('role:admin_spmi')->name('reopen-requests.index');
    Route::post('reopen-requests/{reopenRequest}/resolve', [ReopenRequestController::class, 'resolve'])
        ->middleware('role:admin_spmi')->name('reopen-requests.resolve');

    Route::middleware('role:admin_spmi|auditor')->group(function () {
        Route::resource('findings', FindingController::class)->only(['edit', 'update']);
    });

    /* ---------- PENGENDALIAN: RTL ---------- */
    Route::middleware(['role:admin_spmi|auditee', 'stage:pengendalian'])->group(function () {
        Route::resource('corrective-actions', CorrectiveActionController::class)->except(['index', 'show']);
    });

    Route::post('corrective-actions/{id}/remind', [CorrectiveActionController::class, 'remind'])
        ->middleware('role:admin_spmi|auditor')->name('corrective-actions.remind');

    Route::middleware(['role:admin_spmi|auditor', 'stage:pengendalian'])->group(function () {
        Route::post('corrective-actions/{id}/verify', [CorrectiveActionController::class, 'verify'])->name('corrective-actions.verify');
    });
});
