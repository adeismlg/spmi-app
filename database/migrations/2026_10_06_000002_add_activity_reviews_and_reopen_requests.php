<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('self_evaluation_id')->constrained('activities')->nullOnDelete();
            $table->string('status', 30)->default('pending_review')->after('url');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('superseded_by_id')->nullable()->after('reviewed_at')
                ->constrained('evidences')->nullOnDelete();
        });

        Schema::create('evidence_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidences')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30);
            $table->text('komentar')->nullable();
            $table->timestamps();

            $table->index(['evidence_id', 'created_at']);
        });

        Schema::create('reopen_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('self_evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('alasan');
            $table->string('status', 20)->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_admin')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['self_evaluation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reopen_requests');
        Schema::dropIfExists('evidence_reviews');

        Schema::table('evidences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropColumn(['status', 'reviewed_at']);
        });
    }
};
