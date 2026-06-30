<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignment_history', function (Blueprint $table) {
            $table->id();
            // No foreign key on assignment_id on purpose: the history is an
            // append-only audit trail that must survive an assignment being
            // deleted (e.g. when a candidate is unassigned).
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('evaluator_id');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('candidate_id');
            $table->index('assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_history');
    }
};
