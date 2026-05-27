<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('report_type', 50);
            $table->json('params')->nullable();
            $table->string('file_path')->nullable();
            $table->string('format', 10)->default('PDF');
            $table->enum('status', ['QUEUED', 'PROCESSING', 'DONE', 'FAILED'])->default('QUEUED');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_jobs');
    }
};
