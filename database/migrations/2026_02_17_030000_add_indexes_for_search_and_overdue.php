<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            // Index for experience filters
            $table->index('years_of_experience', 'candidates_years_of_experience_index');
            // Index for specialty filters (if present)
            if (Schema::hasColumn('candidates', 'primary_specialty')) {
                $table->index('primary_specialty', 'candidates_primary_specialty_index');
            }
        });

        Schema::table('candidate_assignments', function (Blueprint $table) {
            // Index for overdue queries
            $table->index('deadline', 'candidate_assignments_deadline_index');
            // Composite index to speed queries by evaluator and status
            $table->index(['evaluator_id', 'status'], 'candidate_assignments_evaluator_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('candidates_years_of_experience_index');
            if (Schema::hasColumn('candidates', 'primary_specialty')) {
                $table->dropIndex('candidates_primary_specialty_index');
            }
        });

        Schema::table('candidate_assignments', function (Blueprint $table) {
            $table->dropIndex('candidate_assignments_deadline_index');
            $table->dropIndex('candidate_assignments_evaluator_status_index');
        });
    }
};

