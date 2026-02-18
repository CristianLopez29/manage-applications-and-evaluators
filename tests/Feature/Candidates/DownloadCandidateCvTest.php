<?php

namespace Tests\Feature\Candidates;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DownloadCandidateCvTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_download_candidate_pdf(): void
    {
        Storage::fake();
        $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $this->post('/api/candidates', [
            'name' => 'PDF Candidate',
            'email' => 'pdf.dl@example.com',
            'years_of_experience' => 4,
            'cv_file' => $file,
        ])->assertStatus(201);

        $model = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'pdf.dl@example.com')->firstOrFail();

        $response = $this->get("/api/candidates/{$model->id}/cv");
        $response->assertStatus(200);
    }

    #[Test]
    public function candidate_can_download_own_pdf_but_not_others(): void
    {
        Storage::fake();
        $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $this->post('/api/candidates', [
            'name' => 'A',
            'email' => 'a@example.com',
            'years_of_experience' => 3,
            'cv_file' => $file,
        ])->assertStatus(201);
        $candA = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'a@example.com')->firstOrFail();

        $this->post('/api/candidates', [
            'name' => 'B',
            'email' => 'b@example.com',
            'years_of_experience' => 3,
            'cv_file' => $file,
        ])->assertStatus(201);
        $candB = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'b@example.com')->firstOrFail();

        $user = User::factory()->create([
            'role' => 'candidate',
            'candidate_id' => $candA->id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->get("/api/candidates/{$candA->id}/cv")->assertStatus(200);
        $this->get("/api/candidates/{$candB->id}/cv")->assertStatus(403);
    }
}

