<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Infrastructure\Persistence\CandidateAssignmentModel;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;

class AssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $evaluators = EvaluatorModel::all();
        $candidates = CandidateModel::all();

        if ($evaluators->isEmpty() || $candidates->isEmpty()) {
            $this->command->warn('No se encontraron evaluadores o candidatos. Ejecuta los seeders primero.');
            return;
        }

        // Assign 3-5 candidates per evaluator
        $candidateIndex = 0;
        $statuses = ['pending', 'in_progress', 'completed', 'rejected'];

        foreach ($evaluators as $evaluator) {
            $candidatesToAssign = rand(3, 5); // Each evaluator receives between 3 and 5 candidates

            for ($i = 0; $i < $candidatesToAssign; $i++) {
                if ($candidateIndex >= $candidates->count()) {
                    break; // No more candidates to assign
                }

                $candidate = $candidates[$candidateIndex];

                // Verify that the candidate is not already assigned
                $existingAssignment = CandidateAssignmentModel::where('candidate_id', $candidate->id)->first();

                if (!$existingAssignment) {
                    CandidateAssignmentModel::create([
                        'evaluator_id' => $evaluator->id,
                        'candidate_id' => $candidate->id,
                        'status' => $statuses[array_rand($statuses)],
                        'assigned_at' => now()->subDays(rand(1, 30)),
                    ]);
                }

                $candidateIndex++;
            }
        }

        $this->command->info('Assignments created successfully.');
    }
}
