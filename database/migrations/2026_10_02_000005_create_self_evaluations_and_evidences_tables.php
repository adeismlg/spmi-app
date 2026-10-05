<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->decimal('capaian', 12, 2)->nullable();
            $table->decimal('skor', 5, 2)->nullable();
            $table->text('uraian')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'unit_id', 'indicator_id']);
        });

        Schema::create('evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('self_evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidences');
        Schema::dropIfExists('self_evaluations');
    }
};
