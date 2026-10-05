<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->text('akar_masalah')->nullable();
            $table->text('tindakan');
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('target_selesai')->nullable();
            $table->string('status', 20)->default('rencana');
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('management_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->date('tanggal');
            $table->text('notulen')->nullable();
            $table->text('keputusan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('finding_management_review', function (Blueprint $table) {
            $table->foreignId('finding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('management_review_id')->constrained()->cascadeOnDelete();
            $table->primary(['finding_id', 'management_review_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_management_review');
        Schema::dropIfExists('management_reviews');
        Schema::dropIfExists('corrective_actions');
    }
};
