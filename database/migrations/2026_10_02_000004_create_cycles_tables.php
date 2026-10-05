<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();
            $table->string('nama');
            $table->string('tahap_aktif', 20)->default('penetapan');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Satu baris per tahap PPEPP: jadwal & status tiap fase.
        Schema::create('cycle_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->string('tahap', 20);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status', 20)->default('belum');
            $table->timestamps();

            $table->unique(['cycle_id', 'tahap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_stages');
        Schema::dropIfExists('cycles');
    }
};
