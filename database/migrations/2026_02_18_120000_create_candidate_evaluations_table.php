<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidate_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->text('summary')->nullable();
            $table->json('skills')->nullable();
            $table->integer('years_experience')->nullable();
            $table->enum('seniority_level', ['Junior', 'Mid', 'Senior', 'Lead'])->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->index('candidate_id', 'candidate_evaluations_candidate_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_evaluations');
    }
};

