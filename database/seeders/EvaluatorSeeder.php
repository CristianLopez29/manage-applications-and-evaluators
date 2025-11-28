<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;

class EvaluatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $evaluators = [
            [
                'name' => 'Dr. Alberto Martínez',
                'email' => 'alberto.martinez@nalanda.com',
                'specialty' => 'Backend',
                'created_at' => now()->subMonths(6),
            ],
            [
                'name' => 'Ing. Lucía Fernández',
                'email' => 'lucia.fernandez@nalanda.com',
                'specialty' => 'Frontend',
                'created_at' => now()->subMonths(5),
            ],
            [
                'name' => 'David Sánchez',
                'email' => 'david.sanchez@nalanda.com',
                'specialty' => 'Fullstack',
                'created_at' => now()->subMonths(4),
            ],
            [
                'name' => 'Marta López',
                'email' => 'marta.lopez@nalanda.com',
                'specialty' => 'DevOps',
                'created_at' => now()->subMonths(3),
            ],
            [
                'name' => 'Roberto García',
                'email' => 'roberto.garcia@nalanda.com',
                'specialty' => 'Mobile',
                'created_at' => now()->subMonths(2),
            ],
        ];

        foreach ($evaluators as $evaluatorData) {
            EvaluatorModel::create($evaluatorData);
        }
    }
}
