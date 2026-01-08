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
        Schema::create('endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_version_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('method');
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->json('parameters')->nullable();
            $table->json('responses')->nullable();
            $table->json('request_body')->nullable();
            $table->json('security')->nullable();
            $table->timestamps();

            $table->index(['contract_version_id', 'path', 'method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoints');
    }
};
