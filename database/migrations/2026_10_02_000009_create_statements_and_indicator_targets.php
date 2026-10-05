<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 24 standar dikelompokkan: 1-8 Pembelajaran, 9-16 Penelitian, 17-24 PkM.
        Schema::table('standards', function (Blueprint $table) {
            $table->unsignedTinyInteger('nomor')->nullable()->after('kode');
            $table->string('kelompok', 20)->nullable()->after('kategori');
        });

        // Satu baris dokumen standar = satu pernyataan (mis. 1.01) beserta PIC, periode, referensi.
        Schema::create('statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->string('nomor', 20)->nullable();           // "1.01"
            $table->text('pernyataan')->nullable();            // teks lengkap (Revisi Berbasis ABCD)
            $table->text('abcd_audience')->nullable();         // [A]
            $table->text('abcd_behaviour')->nullable();        // [B]
            $table->text('abcd_condition')->nullable();        // [C]
            $table->text('abcd_degree')->nullable();           // [D]
            $table->string('pic')->nullable();                 // PIC (Revisi), teks bebas
            $table->string('periode_evaluasi', 40)->nullable(); // App\Enums\EvaluationPeriod
            $table->text('referensi')->nullable();
            $table->string('status', 20)->default('aktif');   // App\Enums\StatementStatus
            $table->text('catatan')->nullable();               // catatan reviewer
            $table->timestamps();

            $table->unique(['standard_id', 'nomor']);
        });

        Schema::table('indicators', function (Blueprint $table) {
            $table->foreignId('statement_id')->nullable()->after('standard_id')->constrained()->nullOnDelete();
            $table->string('tipe', 20)->default('persen')->after('nama'); // App\Enums\IndicatorType
            $table->text('catatan')->nullable()->after('bobot');
        });

        // Baseline (2025) & target per tahun (2026-2030).
        Schema::create('indicator_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('jenis', 10);                        // baseline | target
            $table->unsignedSmallInteger('tahun');
            $table->decimal('nilai', 16, 4)->nullable();        // persen disimpan 0-100
            $table->string('operator', 2)->nullable();          // > >= < <=
            $table->string('teks')->nullable();                 // nilai kualitatif / keterangan
            $table->timestamps();

            $table->unique(['indicator_id', 'jenis', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_targets');

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropConstrainedForeignId('statement_id');
            $table->dropColumn(['tipe', 'catatan']);
        });

        Schema::dropIfExists('statements');

        Schema::table('standards', function (Blueprint $table) {
            $table->dropColumn(['nomor', 'kelompok']);
        });
    }
};
