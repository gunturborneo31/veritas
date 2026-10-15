<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class StudioTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private const DRAFT_TOKEN = 'token-anonim-pengunjung-pertama';

    public function test_guest_creates_anonymous_draft_owned_by_draft_cookie(): void
    {
        $template = $this->createTemplate();

        $response = $this->post(route('studio.store', $template));

        $greeting = Greeting::sole();
        $response->assertRedirect(route('studio.edit', $greeting))->assertCookie('kk_draft');
        $this->assertNull($greeting->user_id);
        $this->assertSame(DraftToken::hash($response->getCookie('kk_draft')->getValue()), $greeting->draft_token_hash);
    }

    public function test_unpublished_template_cannot_be_used_for_a_draft(): void
    {
        $template = $this->createTemplate();
        $template->update(['is_published' => false]);

        $this->post(route('studio.store', $template))->assertNotFound();
        $this->assertDatabaseCount('greetings', 0);
    }

    public function test_owner_autosaves_content_and_unknown_keys_are_dropped(): void
    {
        $greeting = $this->anonymousDraft();

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.update', $greeting), [
                'recipient_name' => 'Sarah',
                'occasion' => 'Ulang Tahun',
                'content' => ['components' => [
                    'surat' => ['body' => 'Halo Sarah', 'injected' => 'abaikan'],
                    'tidak_ada' => ['x' => 'y'],
                ]],
            ])
            ->assertSessionHasNoErrors();

        $greeting->refresh();
        $this->assertSame('Sarah', $greeting->recipient_name);
        $this->assertSame(['surat' => ['body' => 'Halo Sarah']], $greeting->content['components']);
    }

    public function test_other_visitor_gets_404_for_someone_elses_draft(): void
    {
        $greeting = $this->anonymousDraft();

        $this->withCookie('kk_draft', 'token-pengunjung-lain')->get(route('studio.edit', $greeting))->assertNotFound();
        $this->withCookie('kk_draft', 'token-pengunjung-lain')
            ->put(route('studio.update', $greeting), ['recipient_name' => 'Peretas'])
            ->assertNotFound();
        $this->assertNotSame('Peretas', $greeting->refresh()->recipient_name);
    }

    public function test_gate_pin_is_stored_as_hash_and_never_sent_back_to_the_browser(): void
    {
        $greeting = $this->anonymousDraft();

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)->put(route('studio.update', $greeting), [
            'content' => ['components' => ['gembok' => ['enabled' => true, 'question' => 'Tanggal jadian?', 'pin' => '1402']]],
        ])->assertSessionHasNoErrors();

        $gate = $greeting->gate()->sole();
        $this->assertTrue(Hash::check('1402', $gate->answer_hash));
        $this->assertArrayNotHasKey('pin', $greeting->refresh()->content['components']['gembok']);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->get(route('studio.edit', $greeting))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Studio/Edit')
                ->where('greeting.values.gembok.pin', ['is_set' => true, 'length' => 4])
                ->missing('greeting.gate.answer_hash'));
    }

    public function test_invalid_pin_format_is_rejected(): void
    {
        $greeting = $this->anonymousDraft();

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.update', $greeting), ['content' => ['components' => ['gembok' => ['enabled' => true, 'pin' => '12a']]]])
            ->assertSessionHasErrors(['content.components.gembok.pin' => 'PIN harus berupa 4–6 angka.']);
    }

    public function test_published_greeting_cannot_be_edited_through_the_studio(): void
    {
        $greeting = Greeting::factory()
            ->for($this->createTemplate())
            ->published()
            ->create(['recipient_name' => 'Sarah']);

        $this->actingAs($greeting->user)
            ->put(route('studio.update', $greeting), ['recipient_name' => 'Nama Lain'])
            ->assertSessionHas('error');

        $this->assertSame('Sarah', $greeting->refresh()->recipient_name);
    }

    private function anonymousDraft(): Greeting
    {
        return Greeting::factory()
            ->for($this->createTemplate())
            ->anonymous(DraftToken::hash(self::DRAFT_TOKEN))
            ->status(GreetingStatus::Draf)
            ->create();
    }
}
