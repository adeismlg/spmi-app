<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jabatan pengguna (Ketua Unit, Ketua Jurusan, Koordinator Prodi, Wadir I-III, Direktur).
        Schema::table('users', function (Blueprint $table) {
            $table->string('jabatan', 30)->nullable()->after('unit_id');
        });

        // Jenjang prodi (untuk standar yang hanya berlaku pada jenjang tertentu) & singkatan unit.
        Schema::table('units', function (Blueprint $table) {
            $table->string('jenjang', 10)->nullable()->after('tipe');
            $table->string('singkatan', 40)->nullable()->after('nama');
        });

        // PIC baku + sasaran pada pernyataan. Kolom `pic` lama tetap menyimpan teks asli dokumen.
        Schema::table('statements', function (Blueprint $table) {
            $table->json('pic_jabatan')->nullable()->after('pic');
            $table->json('pic_unit')->nullable()->after('pic_jabatan');
            $table->json('jenjang')->nullable()->after('pic_unit');
        });

        // Penugasan: pernyataan mana berlaku untuk unit mana, dan jabatan siapa yang mengisinya.
        Schema::create('statement_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('jabatan', 30);
            $table->string('sumber', 10)->default('otomatis'); // otomatis | manual
            $table->timestamps();

            $table->unique(['statement_id', 'unit_id', 'jabatan']);
            $table->index('unit_id');
        });

        // Evaluasi diri per semester (1 = ganjil, 2 = genap).
        // Unique baru dibuat SEBELUM yang lama dihapus karena foreign key cycle_id membutuhkan indeks.
        Schema::table('self_evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester')->default(2)->after('indicator_id');
            $table->unique(['cycle_id', 'unit_id', 'indicator_id', 'semester'], 'self_eval_unique_semester');
        });
        Schema::table('self_evaluations', function (Blueprint $table) {
            $table->dropUnique(['cycle_id', 'unit_id', 'indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::table('self_evaluations', function (Blueprint $table) {
            $table->unique(['cycle_id', 'unit_id', 'indicator_id']);
        });
        Schema::table('self_evaluations', function (Blueprint $table) {
            $table->dropUnique('self_eval_unique_semester');
            $table->dropColumn('semester');
        });

        Schema::dropIfExists('statement_assignments');

        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn(['pic_jabatan', 'pic_unit', 'jenjang']);
        });
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['jenjang', 'singkatan']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jabatan');
        });
    }
};
