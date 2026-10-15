<?php

namespace Tests\Feature;

use App\Actions\Greetings\PublishGreeting;
use App\Enums\GreetingStatus;
use App\Models\Contribution;
use App\Models\Greeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Mode patungan: teman ikut menulis lewat link, tulisan dibekukan ke snapshot saat terbit.
 */
class ContributionTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_owner_opens_contribution_link_and_friend_contributes_without_account(): void
    {
        Storage::fake('local');
        $greeting = Greeting::factory()->for($this->createTemplate())->create();

        $this->actingAs($greeting->user)->post(route('studio.contributions.enable', $greeting))->assertSessionHasNoErrors();
        $token = $greeting->refresh()->contribution_token;
        $this->assertNotNull($token);

        auth()->logout();

        $this->get(route('contributions.show', $token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Contribute/Show')->where('isOpen', true));

        $this->post(route('contributions.store', $token), [
            'contributor_name' => 'Bima',
            'message' => 'Selamat ya!',
            'photo' => UploadedFile::fake()->image('kita.jpg'),
        ])->assertSessionHasNoErrors();

        $contribution = $greeting->contributions()->sole();
        $this->assertSame('Bima', $contribution->contributor_name);
        $this->assertNotNull($contribution->media_id);
    }

    public function test_contribution_requires_name_and_message(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['contribution_token' => 'tokenpatungan']);

        $this->post(route('contributions.store', 'tokenpatungan'), [])
            ->assertSessionHasErrors(['contributor_name' => 'Tulis namamu dulu, ya.', 'message' => 'Pesannya jangan kosong 😊']);
    }

    public function test_contributions_close_once_greeting_is_locked(): void
    {
        Greeting::factory()->for($this->createTemplate())->status(GreetingStatus::Terkunci)->create(['contribution_token' => 'tokenpatungan']);

        $this->post(route('contributions.store', 'tokenpatungan'), ['contributor_name' => 'Bima', 'message' => 'Telat'])->assertStatus(410);
    }

    public function test_other_user_cannot_manage_someone_elses_contributions(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();
        $contribution = Contribution::factory()->for($greeting)->create();

        $this->actingAs(User::factory()->create())->post(route('studio.contributions.enable', $greeting))->assertNotFound();
        $this->actingAs(User::factory()->create())->patch(route('studio.contributions.toggle', [$greeting, $contribution]))->assertNotFound();
    }

    public function test_only_visible_contributions_are_frozen_into_snapshot_and_link_closes(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->status(GreetingStatus::Terkunci)->create(['contribution_token' => 'tokenpatungan']);
        Contribution::factory()->for($greeting)->create(['contributor_name' => 'Bima', 'message' => 'Tampil']);
        Contribution::factory()->for($greeting)->hidden()->create(['message' => 'Disembunyikan']);

        $snapshot = app(PublishGreeting::class)->handle($greeting);

        $this->assertSame([['name' => 'Bima', 'message' => 'Tampil', 'photo' => null]], $snapshot->content['contributions']);
        $this->assertNull($greeting->refresh()->contribution_token);

        $this->post(route('recipient.open', $greeting->slug));
        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page->where('greeting.contributions.0.message', 'Tampil')->has('greeting.contributions', 1));
    }
}
