<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallTemplateCoversTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundled_cover_is_installed_only_where_a_file_exists_and_no_cover_is_set(): void
    {
        // Sampul bawaan yang memang ada di repo.
        $withFile = Template::factory()->create(['slug' => 'tiup-lilin', 'cover_path' => null]);
        $withoutFile = Template::factory()->create(['slug' => 'template-tanpa-sampul', 'cover_path' => null]);
        $uploaded = Template::factory()->create(['slug' => 'janji-suci', 'cover_path' => 'templates/unggahan-admin.jpg']);

        $this->artisan('kartukita:install-covers')->expectsOutput('1 sampul dipasang.')->assertSuccessful();

        $this->assertSame('/images/templates/tiup-lilin.webp', $withFile->refresh()->cover_path);
        $this->assertNull($withoutFile->refresh()->cover_path);
        $this->assertSame('templates/unggahan-admin.jpg', $uploaded->refresh()->cover_path);
    }
}
