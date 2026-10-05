<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropUnique(['cycle_id', 'unit_id']);
            $table->unsignedTinyInteger('semester')->default(2)->after('unit_id');
            $table->unique(['cycle_id', 'unit_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropUnique(['cycle_id', 'unit_id', 'semester']);
            $table->dropColumn('semester');
            $table->unique(['cycle_id', 'unit_id']);
        });
    }
};
