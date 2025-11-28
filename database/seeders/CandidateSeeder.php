<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
class CandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $candidates = [
            ['name' => 'Juan Pérez García', 'email' => 'juan.perez@example.com', 'years_of_experience' => 5, 'cv_content' => 'Desarrollador Full Stack con 5 años de experiencia en Laravel, Vue.js y MySQL. Experiencia en arquitectura hexagonal y DDD.'],
            ['name' => 'María González López', 'email' => 'maria.gonzalez@example.com', 'years_of_experience' => 8, 'cv_content' => 'Senior Backend Developer especializada en PHP, Laravel y microservicios. Experta en optimización de bases de datos y escalabilidad.'],
            ['name' => 'Carlos Rodríguez Sánchez', 'email' => 'carlos.rodriguez@example.com', 'years_of_experience' => 3, 'cv_content' => 'Desarrollador Backend con experiencia en Laravel, Redis y Docker. Conocimientos en CI/CD con GitHub Actions.'],
            ['name' => 'Ana Martínez Fernández', 'email' => 'ana.martinez@example.com', 'years_of_experience' => 6, 'cv_content' => 'Full Stack Developer con enfoque en arquitecturas limpias. Experiencia en TDD, patrones de diseño y SOLID.'],
            ['name' => 'Pedro López Ruiz', 'email' => 'pedro.lopez@example.com', 'years_of_experience' => 2, 'cv_content' => 'Junior Developer con experiencia en Laravel y Vue.js. Apasionado por las buenas prácticas y el código limpio.'],
            ['name' => 'Laura Sánchez Díaz', 'email' => 'laura.sanchez@example.com', 'years_of_experience' => 10, 'cv_content' => 'Tech Lead con más de 10 años de experiencia. Especialista en arquitectura de software, mentoring y gestión de equipos.'],
            ['name' => 'Diego Fernández Moreno', 'email' => 'diego.fernandez@example.com', 'years_of_experience' => 4, 'cv_content' => 'Backend Developer con experiencia en APIs RESTful, GraphQL y arquitecturas event-driven.'],
            ['name' => 'Sofia García Romero', 'email' => 'sofia.garcia@example.com', 'years_of_experience' => 7, 'cv_content' => 'Senior Developer especializada en Laravel, MySQL, PostgreSQL y Elasticsearch. Experiencia en alta concurrencia.'],
            ['name' => 'Javier Martín Torres', 'email' => 'javier.martin@example.com', 'years_of_experience' => 1, 'cv_content' => 'Desarrollador Junior recién graduado con prácticas en desarrollo web. Conocimientos de Laravel, HTML, CSS y JavaScript.'],
            ['name' => 'Carmen Ruiz Jiménez', 'email' => 'carmen.ruiz@example.com', 'years_of_experience' => 9, 'cv_content' => 'Arquitecta de Software con amplia experiencia en diseño de sistemas distribuidos, microservicios y cloud computing.'],
            ['name' => 'Miguel Díaz Navarro', 'email' => 'miguel.diaz@example.com', 'years_of_experience' => 5, 'cv_content' => 'DevOps Engineer con experiencia en Laravel, Docker, Kubernetes y automatización de despliegues.'],
            ['name' => 'Isabel Moreno Vázquez', 'email' => 'isabel.moreno@example.com', 'years_of_experience' => 3, 'cv_content' => 'Desarrolladora con enfoque en testing. Experiencia en PHPUnit, Pest y pruebas de integración.'],
            ['name' => 'Antonio Torres Ramos', 'email' => 'antonio.torres@example.com', 'years_of_experience' => 6, 'cv_content' => 'Full Stack Developer con experiencia en Laravel, React y Node.js. Conocimientos en arquitecturas serverless.'],
            ['name' => 'Beatriz Jiménez Castro', 'email' => 'beatriz.jimenez@example.com', 'years_of_experience' => 0, 'cv_content' => 'Recién graduada en Ingeniería Informática. Proyectos académicos con Laravel y bases de datos relacionales.'],
            ['name' => 'Francisco Navarro Gil', 'email' => 'francisco.navarro@example.com', 'years_of_experience' => 4, 'cv_content' => 'Backend Developer especializado en APIs y integraciones. Experiencia con Laravel, Symfony y PSR standards.'],
            ['name' => 'Elena Vázquez Ortiz', 'email' => 'elena.vazquez@example.com', 'years_of_experience' => 8, 'cv_content' => 'Senior Developer con experiencia en e-commerce de alto tráfico. Especialista en optimización de rendimiento.'],
            ['name' => 'Roberto Ramos Serrano', 'email' => 'roberto.ramos@example.com', 'years_of_experience' => 2, 'cv_content' => 'Desarrollador Backend con conocimientos en Laravel, Redis y colas de mensajería. Interesado en escalabilidad.'],
            ['name' => 'Patricia Castro Molina', 'email' => 'patricia.castro@example.com', 'years_of_experience' => 7, 'cv_content' => 'Tech Lead especializada en arquitecturas hexagonales y DDD. Mentora de equipos junior.'],
            ['name' => 'Raúl Gil Medina', 'email' => 'raul.gil@example.com', 'years_of_experience' => 1, 'cv_content' => 'Junior Developer con conocimientos básicos de PHP y Laravel. Sin CV completo pero con mucha motivación.'],
            ['name' => 'Cristina Ortiz Muñoz', 'email' => 'cristina.ortiz@example.com', 'years_of_experience' => 11, 'cv_content' => 'Staff Engineer con más de 11 años de experiencia. Especialista en arquitectura de software, performance y escalabilidad horizontal.'],
        ];

        foreach ($candidates as $candidateData) {
            CandidateModel::create($candidateData);
        }
    }
}
