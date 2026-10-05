<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_desk_evaluation')->nullable();
            $table->date('tanggal_visitasi')->nullable();
            $table->string('status', 20)->default('terjadwal');
            $table->text('ringkasan')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'unit_id']);
        });

        Schema::create('audit_auditors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('peran', 20)->default('anggota');
            $table->timestamps();

            $table->unique(['audit_id', 'user_id']);
        });

        Schema::create('audit_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('self_evaluation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kesesuaian', 20)->nullable();
            $table->decimal('skor_audit', 5, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['audit_id', 'indicator_id']);
        });

        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_checklist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kategori', 20);
            $table->text('uraian');
            $table->text('rekomendasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
        Schema::dropIfExists('audit_checklists');
        Schema::dropIfExists('audit_auditors');
        Schema::dropIfExists('audits');
    }
};
