<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->string('nomor')->nullable();
            $table->string('judul');
            $table->string('versi', 20)->default('1.0');
            $table->string('status', 20)->default('draft');
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->date('tanggal_berlaku')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
