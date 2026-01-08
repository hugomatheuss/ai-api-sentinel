<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('validation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_version_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['passed', 'failed', 'warning'])->default('passed');
            $table->json('report_json')->nullable();
            $table->json('issues')->nullable();
            $table->json('breaking_changes')->nullable();
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('contract_version_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_reports');
    }
};
