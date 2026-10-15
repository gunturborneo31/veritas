<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\MediaKind;
use App\Enums\MediaStatus;
use App\Models\Greeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Duplikat ucapan, kirim terjadwal dari Studio, dan loop rujukan "balas dengan kejutan".
 */
class GreetingGrowthTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_duplicate_creates_new_draft_with_copied_media_and_without_recipient(): void
    {
        Storage::fake('local');
        $source = Greeting::factory()->for($this->createTemplate())->published()->create();
        Storage::disk('local')->put('greetings/asal/foto.webp', 'gambar');
        $media = $source->media()->create([
            'kind' => MediaKind::Image, 'status' => MediaStatus::Siap, 'disk' => 'local',
            'path' => 'greetings/asal/foto.webp', 'mime_type' => 'image/webp', 'size' => 6,
        ]);
        $source->forceFill(['content' => ['components' => ['sampul' => ['title' => 'Halo', 'photo' => $media->public_id]]]])->saveQuietly();

        $response = $this->actingAs($source->user)->post(route('dashboard.greetings.duplicate', $source));

        $copy = Greeting::query()->whereKeyNot($source->id)->sole();
        $response->assertRedirect(route('studio.edit', $copy));
        $this->assertSame(GreetingStatus::Draf, $copy->status);
        $this->assertNull($copy->recipient_name);
        $copiedMediaId = $copy->content['components']['sampul']['photo'];
        $this->assertNotSame($media->public_id, $copiedMediaId);
        Storage::disk('local')->assertExists($copy->media()->sole()->path);
    }

    public function test_user_cannot_duplicate_someone_elses_greeting(): void
    {
        $source = Greeting::factory()->for($this->createTemplate())->create();

        $this->actingAs(User::factory()->create())->post(route('dashboard.greetings.duplicate', $source))->assertNotFound();
    }

    public function test_studio_saves_schedule_and_rejects_past_time(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();

        $this->actingAs($greeting->user)
            ->put(route('studio.update', $greeting), ['opens_at' => now()->subHour()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('opens_at');

        $this->actingAs($greeting->user)
            ->put(route('studio.update', $greeting), ['opens_at' => now()->addDay()->setTime(0, 0)->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();

        $this->assertTrue($greeting->refresh()->opens_at->equalTo(now()->addDay()->setTime(0, 0)));
    }

    public function test_recipient_who_creates_a_greeting_is_attributed_to_the_original(): void
    {
        $template = $this->createTemplate();
        $original = Greeting::factory()->for($template)->published()->create();

        $this->get(route('catalog.index', ['dari' => $original->slug]))->assertOk();
        $this->post(route('studio.store', $template));

        $this->assertSame($original->id, Greeting::query()->latest('id')->first()->referred_by_greeting_id);
    }
}
