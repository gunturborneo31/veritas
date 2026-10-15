<?php

namespace Tests\Feature;

use App\Models\Greeting;
use App\Models\Reaction;
use App\Models\User;
use App\Notifications\GreetingReacted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class ReactionVideoTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_video_requires_explicit_consent(): void
    {
        $greeting = $this->openedGreeting();

        $this->post(route('recipient.video', $greeting->slug), ['video' => $this->fakeVideo()])
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_consented_video_is_stored_privately_once_and_notifies_sender(): void
    {
        Storage::fake('local');
        Notification::fake();
        $greeting = $this->openedGreeting();

        $this->post(route('recipient.video', $greeting->slug), ['consent' => true, 'video' => $this->fakeVideo()])->assertSessionHasNoErrors();
        $this->post(route('recipient.video', $greeting->slug), ['consent' => true, 'video' => $this->fakeVideo()]);

        $reaction = $greeting->reactions()->sole();
        $this->assertSame(Reaction::KIND_VIDEO, $reaction->kind);
        Storage::disk('local')->assertExists($reaction->value);
        Notification::assertSentTo($greeting->user, GreetingReacted::class);
    }

    public function test_video_cannot_be_sent_before_opening(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create();

        $this->post(route('recipient.video', $greeting->slug), ['consent' => true, 'video' => $this->fakeVideo()])->assertForbidden();
    }

    public function test_only_the_owner_can_delete_a_reaction_video(): void
    {
        Storage::fake('local');
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create();
        Storage::disk('local')->put('reaction-videos/x/v.webm', 'video');
        $reaction = $greeting->reactions()->create(['kind' => Reaction::KIND_VIDEO, 'value' => 'reaction-videos/x/v.webm']);

        $this->actingAs(User::factory()->create())->delete(route('reaction-videos.destroy', $reaction))->assertNotFound();
        $this->actingAs($greeting->user)->delete(route('reaction-videos.destroy', $reaction))->assertRedirect();

        $this->assertModelMissing($reaction);
        Storage::disk('local')->assertMissing('reaction-videos/x/v.webm');
    }

    private function openedGreeting(): Greeting
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create();
        $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome/128')->post(route('recipient.open', $greeting->slug));

        return $greeting;
    }

    private function fakeVideo(): UploadedFile
    {
        return UploadedFile::fake()->create('reaksi.webm', 500, 'video/webm');
    }
}
