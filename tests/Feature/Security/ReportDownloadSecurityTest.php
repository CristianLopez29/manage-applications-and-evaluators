<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportDownloadSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_download_report_from_private_disk(): void
    {
        Storage::fake('reports');

        $fileName = 'evaluators_123.xlsx';
        Storage::disk('reports')->put($fileName, 'dummy content');

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->get("/api/reports/download?file={$fileName}");

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    #[Test]
    public function non_admin_cannot_download_report(): void
    {
        Storage::fake('reports');

        $fileName = 'evaluators_123.xlsx';
        Storage::disk('reports')->put($fileName, 'dummy content');

        $user = User::factory()->create(['role' => 'candidate']);
        Sanctum::actingAs($user, ['*']);

        $this->get("/api/reports/download?file={$fileName}")
            ->assertStatus(403);
    }
}

