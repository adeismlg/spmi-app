<?php

namespace Database\Seeders;

use App\Enums\ActionStatus;
use App\Enums\AuditStatus;
use App\Enums\Conformity;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\EvaluationPeriod;
use App\Enums\EvidenceStatus;
use App\Enums\IndicatorType;
use App\Enums\Jabatan;
use App\Enums\Jenjang;
use App\Enums\Semester;
use App\Enums\SubmissionStatus;
use App\Enums\UnitType;
use App\Models\Activity;
use App\Models\Audit;
use App\Models\AuditChecklist;
use App\Models\CorrectiveAction;
use App\Models\Cycle;
use App\Models\Document;
use App\Models\Evidence;
use App\Models\EvidenceReview;
use App\Models\Indicator;
use App\Models\ManagementReview;
use App\Models\SelfEvaluation;
use App\Models\Standard;
use App\Models\Statement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $cycle = Cycle::current() ?? Cycle::query()->orderByDesc('tahun')->first();
            if (! $cycle) {
                throw new RuntimeException('Data demo memerlukan siklus SPMI aktif.');
            }

            $institution = Unit::where('kode', 'INST')->firstOrFail();
            $unit = Unit::firstOrCreate(
                ['kode' => 'DEMO-PRODI'],
                [
                    'nama' => 'Program Studi Contoh (Data Demo)',
                    'tipe' => UnitType::Prodi,
                    'jenjang' => Jenjang::D4,
                    'parent_id' => $institution->id,
                ],
            );
            $demoAuditee = User::updateOrCreate(
                ['email' => 'demo.auditee@spmi.test'],
                [
                    'name' => 'Auditee Demo',
                    'password' => Hash::make('password'),
                    'unit_id' => $unit->id,
                    'jabatan' => Jabatan::KoordinatorProdi,
                ],
            );
            $demoAuditee->syncRoles(['auditee']);

            $demoAuditor = User::updateOrCreate(
                ['email' => 'demo.auditor@spmi.test'],
                ['name' => 'Auditor Demo', 'password' => Hash::make('password'), 'unit_id' => null],
            );
            $demoAuditor->syncRoles(['auditor']);

            $demoDocuments = [
                [
                    'number' => 'DEMO-DOC-001',
                    'type' => DocumentType::Kebijakan,
                    'title' => '[DATA DEMO] Kebijakan Mutu Internal',
                    'version' => '1.0',
                    'status' => DocumentStatus::Valid,
                    'unit_id' => null,
                    'effective_date' => now()->subMonths(2)->toDateString(),
                    'file' => 'kebijakan-mutu',
                    'content' => implode("\n", [
                        'CONTOH DOKUMEN - DATA DEMO, BUKAN KEBIJAKAN RESMI',
                        '',
                        'KEBIJAKAN MUTU INTERNAL',
                        'Nomor: DEMO-DOC-001 | Versi: 1.0',
                        '',
                        'Tujuan',
                        'Contoh komitmen untuk menjalankan penjaminan mutu secara terencana, terukur, transparan, dan berkelanjutan.',
                        '',
                        'Ruang Lingkup',
                        'Contoh ini menggambarkan penerapan PPEPP pada kegiatan pendidikan, penelitian, dan pengabdian kepada masyarakat.',
                        '',
                        'Catatan penggunaan',
                        'Ganti isi contoh, nomor, tanggal, dan pejabat pengesah sesuai dokumen resmi institusi sebelum digunakan.',
                    ]),
                ],
                [
                    'number' => 'DEMO-DOC-002',
                    'type' => DocumentType::Manual,
                    'title' => '[DATA DEMO] Manual Siklus PPEPP',
                    'version' => '1.1',
                    'status' => DocumentStatus::Valid,
                    'unit_id' => null,
                    'effective_date' => now()->subMonth()->toDateString(),
                    'file' => 'manual-ppepp',
                    'content' => implode("\n", [
                        'CONTOH DOKUMEN - DATA DEMO, BUKAN MANUAL RESMI',
                        '',
                        'MANUAL SIKLUS PPEPP',
                        'Nomor: DEMO-DOC-002 | Versi: 1.1',
                        '',
                        '1. Penetapan - tetapkan kebijakan, standar, indikator, target, dan penanggung jawab.',
                        '2. Pelaksanaan - laksanakan kegiatan sesuai standar dan simpan bukti yang relevan.',
                        '3. Evaluasi - lakukan evaluasi diri dan audit terhadap capaian serta bukti.',
                        '4. Pengendalian - tindak lanjuti temuan dengan PIC dan target penyelesaian.',
                        '5. Peningkatan - gunakan hasil tinjauan untuk menetapkan perbaikan siklus berikutnya.',
                        '',
                        'Catatan penggunaan',
                        'Alur ini hanya materi simulasi. Sesuaikan dengan prosedur resmi institusi.',
                    ]),
                ],
                [
                    'number' => 'DEMO-DOC-003',
                    'type' => DocumentType::Standar,
                    'title' => '[DATA DEMO] Standar Evaluasi Pembelajaran',
                    'version' => '0.1',
                    'status' => DocumentStatus::Draft,
                    'unit_id' => $unit->id,
                    'effective_date' => null,
                    'file' => 'standar-evaluasi-pembelajaran',
                    'content' => implode("\n", [
                        'CONTOH DOKUMEN - DATA DEMO, DRAF BELUM DISAHKAN',
                        '',
                        'STANDAR EVALUASI PEMBELAJARAN',
                        'Nomor: DEMO-DOC-003 | Versi: 0.1',
                        'Unit contoh: Program Studi Contoh',
                        '',
                        'Tujuan',
                        'Menjadi bahan simulasi untuk mengukur pencapaian indikator pembelajaran.',
                        '',
                        'Indikator contoh',
                        'Program studi meninjau pencapaian indikator dan menyimpan bukti evaluasi pada setiap periode yang ditetapkan.',
                        '',
                        'Catatan penggunaan',
                        'Status draf. Target, indikator, pengesahan, dan ruang lingkup harus ditinjau sebelum penetapan.',
                    ]),
                ],
                [
                    'number' => 'DEMO-DOC-004',
                    'type' => DocumentType::Formulir,
                    'title' => '[DATA DEMO] Formulir Audit Mutu Internal',
                    'version' => '0.9',
                    'status' => DocumentStatus::Arsip,
                    'unit_id' => $unit->id,
                    'effective_date' => now()->subYear()->toDateString(),
                    'file' => 'formulir-audit-mutu',
                    'content' => implode("\n", [
                        'CONTOH DOKUMEN - DATA DEMO, FORMULIR ARSIP',
                        '',
                        'FORMULIR AUDIT MUTU INTERNAL',
                        'Nomor: DEMO-DOC-004 | Versi: 0.9',
                        '',
                        'Unit yang diaudit: ____________________',
                        'Periode audit: _________________________',
                        'Auditor: _______________________________',
                        '',
                        'Standar / indikator: ____________________',
                        'Bukti yang diperiksa: __________________',
                        'Hasil: Sesuai / Observasi / KTS Minor / KTS Major',
                        'Catatan auditor: ________________________',
                        'Rekomendasi: ___________________________',
                        '',
                        'Catatan penggunaan',
                        'Contoh arsip untuk demonstrasi. Gunakan formulir aktif yang telah disahkan untuk audit sebenarnya.',
                    ]),
                ],
            ];

            foreach ($demoDocuments as $sample) {
                $filePath = 'demo/documents/'.$sample['file'].'.txt';
                if (! Storage::disk('public')->put($filePath, $sample['content'])) {
                    throw new RuntimeException("Gagal menulis berkas dokumen demo: {$filePath}");
                }

                Document::updateOrCreate(
                    ['nomor' => $sample['number']],
                    [
                        'jenis' => $sample['type'],
                        'judul' => $sample['title'],
                        'versi' => $sample['version'],
                        'status' => $sample['status'],
                        'file_path' => $filePath,
                        'url' => null,
                        'tanggal_berlaku' => $sample['effective_date'],
                        'unit_id' => $sample['unit_id'],
                        'uploaded_by' => $demoAuditee->id,
                    ],
                );
            }

            $standards = Standard::query()
                ->where('is_active', true)
                ->where('standard_edition_id', $cycle->standard_edition_id)
                ->orderBy('nomor')
                ->take(2)
                ->get();

            if ($standards->count() < 2) {
                throw new RuntimeException('Data demo memerlukan minimal dua standar aktif.');
            }

            $statements = [];
            foreach ($standards as $index => $standard) {
                $number = $index + 1;
                $statements[$number] = Statement::updateOrCreate(
                    ['standard_id' => $standard->id, 'nomor' => sprintf('DEMO-%02d', $number)],
                    [
                        'pernyataan' => '[DATA DEMO] Pelaksanaan dan evaluasi standar '.$standard->nama,
                        'periode_evaluasi' => EvaluationPeriod::SetiapSemester,
                        'status' => 'aktif',
                        'catatan' => 'Pernyataan contoh untuk simulasi alur PPEPP.',
                    ],
                );
            }

            $indicators = collect([
                ['standard' => 1, 'statement' => 1, 'code' => 'DEMO-01', 'name' => '[DATA DEMO] Ketercapaian indikator standar', 'score' => 86, 'status' => SubmissionStatus::Verified, 'evidence_status' => EvidenceStatus::Verified],
                ['standard' => 1, 'statement' => 1, 'code' => 'DEMO-02', 'name' => '[DATA DEMO] Kelengkapan bukti pelaksanaan', 'score' => 72, 'status' => SubmissionStatus::Submitted, 'evidence_status' => EvidenceStatus::Pending],
                ['standard' => 2, 'statement' => 2, 'code' => 'DEMO-03', 'name' => '[DATA DEMO] Kesesuaian pelaksanaan standar', 'score' => 58, 'status' => SubmissionStatus::Submitted, 'evidence_status' => EvidenceStatus::Revision],
                ['standard' => 2, 'statement' => 2, 'code' => 'DEMO-04', 'name' => '[DATA DEMO] Tindak lanjut hasil evaluasi', 'score' => 64, 'status' => SubmissionStatus::Draft, 'evidence_status' => null],
            ])->map(function (array $sample) use ($standards, $statements): array {
                $standard = $standards[$sample['standard'] - 1];
                $statement = $statements[$sample['statement']];
                $indicator = Indicator::updateOrCreate(
                    ['standard_id' => $standard->id, 'kode' => $sample['code']],
                    [
                        'statement_id' => $statement->id,
                        'nama' => $sample['name'],
                        'tipe' => IndicatorType::Persen,
                        'satuan' => '%',
                        'target' => 80,
                        'bobot' => 1,
                    ],
                );
                $activity = Activity::updateOrCreate(
                    ['indicator_id' => $indicator->id, 'nama' => '[DATA DEMO] Dokumentasi pelaksanaan'],
                    [
                        'deskripsi' => 'Kegiatan dan bukti pendukung untuk simulasi alur PPEPP.',
                        'urutan' => 1,
                        'is_active' => true,
                    ],
                );

                return [...$sample, 'indicator' => $indicator, 'activity' => $activity];
            });

            $evaluations = $indicators->mapWithKeys(function (array $sample) use ($cycle, $unit): array {
                $evaluation = SelfEvaluation::updateOrCreate(
                    [
                        'cycle_id' => $cycle->id,
                        'unit_id' => $unit->id,
                        'indicator_id' => $sample['indicator']->id,
                        'semester' => Semester::Ganjil->value,
                    ],
                    [
                        'capaian' => $sample['score'],
                        'skor' => $sample['score'],
                        'uraian' => 'Data simulasi untuk demonstrasi proses evaluasi diri dan audit mutu.',
                        'status' => $sample['status'],
                        'submitted_at' => $sample['status'] === SubmissionStatus::Draft ? null : now()->subDays(7),
                    ],
                );

                return [$sample['code'] => $evaluation];
            });

            foreach ($indicators as $sample) {
                if (! $sample['evidence_status']) {
                    continue;
                }

                $evidenceStatus = $sample['evidence_status'];
                $reviewed = in_array($evidenceStatus, [EvidenceStatus::Verified, EvidenceStatus::Revision], true);
                $evidence = Evidence::updateOrCreate(
                    [
                        'self_evaluation_id' => $evaluations[$sample['code']]->id,
                        'activity_id' => $sample['activity']->id,
                    ],
                    [
                        'judul' => '[DATA DEMO] Contoh bukti pendukung (simulasi)',
                        'url' => 'https://example.com/',
                        'uploaded_by' => $demoAuditee->id,
                        'status' => $evidenceStatus,
                        'reviewed_by' => $reviewed ? $demoAuditor->id : null,
                        'reviewed_at' => $reviewed ? now()->subDays(2) : null,
                    ],
                );

                if ($reviewed) {
                    EvidenceReview::updateOrCreate(
                        [
                            'evidence_id' => $evidence->id,
                            'reviewer_id' => $demoAuditor->id,
                            'status' => $evidenceStatus,
                        ],
                        [
                            'komentar' => $evidenceStatus === EvidenceStatus::Revision
                                ? '[DATA DEMO] Mohon lengkapi bukti sebelum diajukan kembali.'
                                : '[DATA DEMO] Bukti telah diperiksa.',
                        ],
                    );
                }
            }

            $audit = Audit::updateOrCreate(
                [
                    'cycle_id' => $cycle->id,
                    'unit_id' => $unit->id,
                    'semester' => Semester::Ganjil->value,
                ],
                [
                    'status' => AuditStatus::Selesai,
                    'tanggal_desk_evaluation' => now()->subDays(5)->toDateString(),
                    'tanggal_visitasi' => now()->subDays(2)->toDateString(),
                    'ringkasan' => '[DATA DEMO] Audit selesai dengan beberapa catatan tindak lanjut.',
                ],
            );
            $audit->auditors()->syncWithoutDetaching([$demoAuditor->id => ['peran' => 'ketua']]);

            $findings = collect();
            $checklistSamples = [
                ['code' => 'DEMO-01', 'conformity' => Conformity::Sesuai, 'score' => 90, 'note' => 'Pelaksanaan indikator telah memenuhi target.'],
                ['code' => 'DEMO-02', 'conformity' => Conformity::Observasi, 'score' => 72, 'note' => '[DATA DEMO] Bukti pelaksanaan perlu dilengkapi pada periode berikutnya.'],
                ['code' => 'DEMO-03', 'conformity' => Conformity::KtsMinor, 'score' => 58, 'note' => '[DATA DEMO] Capaian indikator belum konsisten dengan target yang ditetapkan.'],
                ['code' => 'DEMO-04', 'conformity' => Conformity::KtsMajor, 'score' => 40, 'note' => '[DATA DEMO] Prosedur evaluasi belum diterapkan secara menyeluruh.'],
            ];

            foreach ($checklistSamples as $sample) {
                $evaluation = $evaluations[$sample['code']];
                $checklist = AuditChecklist::updateOrCreate(
                    ['audit_id' => $audit->id, 'indicator_id' => $evaluation->indicator_id],
                    [
                        'self_evaluation_id' => $evaluation->id,
                        'kesesuaian' => $sample['conformity'],
                        'skor_audit' => $sample['score'],
                        'catatan' => $sample['note'],
                    ],
                );

                if (! $sample['conformity']->isFinding()) {
                    continue;
                }

                $finding = $checklist->finding()->firstOrFail();
                $finding->update([
                    'rekomendasi' => 'Susun dan dokumentasikan tindakan perbaikan, penanggung jawab, serta bukti penyelesaiannya.',
                ]);
                $findings->push($finding);

                $actionDetails = match ($sample['conformity']) {
                    Conformity::Observasi => [
                        'status' => ActionStatus::Selesai,
                        'target_selesai' => now()->subDay()->toDateString(),
                        'tindakan' => '[DATA DEMO] Melengkapi dokumentasi bukti pelaksanaan.',
                    ],
                    Conformity::KtsMinor => [
                        'status' => ActionStatus::Berjalan,
                        'target_selesai' => now()->subDays(3)->toDateString(),
                        'tindakan' => '[DATA DEMO] Menyelaraskan pelaksanaan dengan target indikator.',
                    ],
                    Conformity::KtsMajor => [
                        'status' => ActionStatus::Rencana,
                        'target_selesai' => now()->addDays(14)->toDateString(),
                        'tindakan' => '[DATA DEMO] Menyusun dan menerapkan prosedur evaluasi standar.',
                    ],
                    Conformity::Sesuai => throw new RuntimeException('Temuan demo tidak boleh berkategori sesuai.'),
                };

                CorrectiveAction::updateOrCreate(
                    ['finding_id' => $finding->id],
                    [
                        'akar_masalah' => '[DATA DEMO] Pemantauan dan dokumentasi belum dilakukan secara konsisten.',
                        'pic_user_id' => $demoAuditee->id,
                        ...$actionDetails,
                    ],
                );
            }

            $review = ManagementReview::updateOrCreate(
                ['cycle_id' => $cycle->id, 'judul' => '[DATA DEMO] Tinjauan Manajemen PPEPP'],
                [
                    'tanggal' => now()->toDateString(),
                    'notulen' => '[DATA DEMO] Pembahasan hasil evaluasi diri, audit, dan tindak lanjut.',
                    'keputusan' => 'Memantau penyelesaian tindakan perbaikan pada tinjauan berikutnya.',
                    'rekomendasi' => 'Perkuat kelengkapan bukti dan pemantauan capaian indikator.',
                ],
            );
            $review->findings()->syncWithoutDetaching($findings->pluck('id')->all());
        });

        $this->command?->info('Data demo alur PPEPP berhasil dibuat atau diperbarui tanpa menghapus data lain.');
    }
}
