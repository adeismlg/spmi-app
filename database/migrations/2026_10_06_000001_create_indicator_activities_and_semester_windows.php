<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['indicator_id', 'is_active', 'urutan']);
        });

        Schema::create('semester_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->dateTime('pengisian_mulai');
            $table->dateTime('pengisian_selesai');
            $table->dateTime('pemeriksaan_mulai');
            $table->dateTime('pemeriksaan_selesai');
            $table->timestamps();

            $table->unique(['cycle_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_windows');
        Schema::dropIfExists('activities');
    }
};
