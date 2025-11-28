<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidate_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('evaluators')->onDelete('cascade');
            $table->string('status', 50)->default('pending');
            $table->timestamp('assigned_at');
            $table->timestamps();

            // Un candidato solo puede estar asignado a un evaluador a la vez
            $table->unique('candidate_id', 'unique_candidate_assignment');

            // Index para la columna 'evaluator_id' para mejorar la velocidad de las consultas
            $table->index('evaluator_id');
            // Index para la columna 'status' para mejorar la velocidad de las consultas
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_assignments');
    }
};
