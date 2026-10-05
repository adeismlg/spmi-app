<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->string('kategori', 20);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->string('kode', 30);
            $table->text('nama');
            $table->string('satuan', 50)->nullable();
            $table->decimal('target', 12, 2)->nullable();
            $table->decimal('bobot', 5, 2)->default(1);
            $table->timestamps();

            $table->unique(['standard_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('standards');
    }
};
