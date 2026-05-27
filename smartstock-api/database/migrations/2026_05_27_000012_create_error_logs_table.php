<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('severity', ['CRITICAL', 'WARNING', 'INFO'])->default('INFO');
            $table->string('message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();

            $table->index(['severity', 'created_at']);
            $table->index('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
