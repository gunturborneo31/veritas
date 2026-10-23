<?php

namespace Tests\Feature;

use App\Enums\MediaKind;
use App\Enums\MediaStatus;
use App\Jobs\ProcessGreetingMedia;
use App\Models\Greeting;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * URL foto berbasis ID media: tetap tampil setelah foto dikompres di latar belakang.
 */
class MediaFileTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['kartukita.media.disk' => 'local']);
        Storage::fake('local');
    }

    public function test_url_given_right_after_upload_still_works_after_compression(): void
    {
        $media = $this->uploadedJpeg();
        $urlFromUpload = $media->toClientArray()['url'];

        (new ProcessGreetingMedia($media))->handle();

        $this->assertStringEndsWith('.webp', $media->refresh()->path);
        Storage::disk('local')->assertMissing(str_replace('.webp', '.jpg', $media->path));
        $this->get($urlFromUpload)->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_media_url_requires_a_valid_signature(): void
    {
        $media = $this->uploadedJpeg();

        $this->get(route('media.file', $media))->assertForbidden();
        $this->get($media->toClientArray()['url'].'x')->assertForbidden();
    }

    public function test_expired_media_url_is_refused(): void
    {
        $url = $this->uploadedJpeg()->temporaryUrl(minutes: 1);

        $this->travel(2)->minutes();

        $this->get($url)->assertForbidden();
    }

    private function uploadedJpeg(): Media
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();
        $path = UploadedFile::fake()->image('foto.jpg', 800, 600)->store("greetings/{$greeting->public_id}", 'local');

        return $greeting->media()->create([
            'kind' => MediaKind::Image,
            'status' => MediaStatus::Menunggu,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 1000,
        ]);
    }
}
