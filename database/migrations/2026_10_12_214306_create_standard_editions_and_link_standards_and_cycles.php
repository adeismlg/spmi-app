<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('standard_editions', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->unsignedSmallInteger('tahun_mulai');
            $table->unsignedSmallInteger('tahun_selesai')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        $legacyEditionId = DB::table('standard_editions')->insertGetId([
            'kode' => 'LEGACY',
            'nama' => 'Edisi Standar Sebelum Versi Periode',
            'tahun_mulai' => 1900,
            'tahun_selesai' => null,
            'deskripsi' => 'Edisi kompatibilitas untuk mempertahankan standar dan siklus yang sudah tersimpan sebelum fitur edisi standar.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('standards', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->foreignId('standard_edition_id')->nullable()->constrained('standard_editions')->restrictOnDelete();
        });

        DB::table('standards')->whereNull('standard_edition_id')->update([
            'standard_edition_id' => $legacyEditionId,
        ]);

        Schema::table('standards', function (Blueprint $table) {
            $table->unique(['standard_edition_id', 'kode']);
        });

        Schema::table('cycles', function (Blueprint $table) {
            $table->foreignId('standard_edition_id')->nullable()->constrained('standard_editions')->restrictOnDelete();
        });

        DB::table('cycles')->whereNull('standard_edition_id')->update([
            'standard_edition_id' => $legacyEditionId,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $duplicates = DB::table('standards')
            ->select('kode')
            ->groupBy('kode')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('Standar dengan kode sama pada beberapa edisi harus diselesaikan sebelum rollback.');
        }

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('standard_edition_id');
        });

        Schema::table('standards', function (Blueprint $table) {
            $table->dropUnique(['standard_edition_id', 'kode']);
            $table->dropConstrainedForeignId('standard_edition_id');
            $table->unique('kode');
        });

        Schema::dropIfExists('standard_editions');
    }
};
