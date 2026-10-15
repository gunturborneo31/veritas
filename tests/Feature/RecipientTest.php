<?php

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\Greeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class RecipientTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private const BROWSER_UA = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/128 Mobile Safari/537.36';

    public function test_gate_screen_does_not_expose_content_or_answer_and_does_not_count_as_opened(): void
    {
        $greeting = $this->publishedGreetingWithGate();

        $this->get(route('recipient.show', $greeting->slug))
            ->assertOk()
            ->assertDontSee('Semoga panjang umur.')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recipient/Gate')
                ->where('gate', ['type' => 'pin', 'question' => 'Tanggal kita pertama bertemu?', 'hint' => 'Formatnya DDMM', 'length' => 4])
                ->missing('greeting'));

        $this->assertDatabaseCount('open_events', 0);
    }

    public function test_wrong_pin_is_rejected_and_content_stays_locked(): void
    {
        $greeting = $this->publishedGreetingWithGate();

        $this->withHeader('User-Agent', self::BROWSER_UA)
            ->post(route('recipient.open', $greeting->slug), ['answer' => '0000'])
            ->assertSessionHasErrors(['answer' => 'Kombinasi belum tepat, coba lagi ya!']);

        $this->get(route('recipient.show', $greeting->slug))->assertInertia(fn (Assert $page) => $page->component('Recipient/Gate'));
        $this->assertDatabaseCount('open_events', 0);
    }

    public function test_correct_pin_unlocks_snapshot_content_and_records_open_event(): void
    {
        $greeting = $this->publishedGreetingWithGate();

        $this->withHeader('User-Agent', self::BROWSER_UA)
            ->post(route('recipient.open', $greeting->slug), ['answer' => '1402'])
            ->assertRedirect(route('recipient.show', $greeting->slug));

        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recipient/Play')
                ->where('greeting.values.surat.body', 'Semoga panjang umur.'));
        $this->assertDatabaseCount('open_events', 1);
    }

    public function test_pin_attempts_are_rate_limited(): void
    {
        $greeting = $this->publishedGreetingWithGate();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('recipient.open', $greeting->slug), ['answer' => '0000']);
        }

        $this->post(route('recipient.open', $greeting->slug), ['answer' => '1402'])->assertSessionHasErrors('answer');
        $this->get(route('recipient.show', $greeting->slug))->assertInertia(fn (Assert $page) => $page->component('Recipient/Gate'));
    }

    public function test_link_preview_bot_and_owner_are_not_counted_as_opens(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create(['content' => $this->completeContent()]);

        $this->withHeader('User-Agent', 'WhatsApp/2.23.20.0 A')->post(route('recipient.open', $greeting->slug));
        $this->actingAs($greeting->user)->withHeader('User-Agent', self::BROWSER_UA)->post(route('recipient.open', $greeting->slug));

        $this->assertDatabaseCount('open_events', 0);
    }

    public function test_reply_requires_the_greeting_to_be_opened_first(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create(['content' => $this->completeContent()]);

        $this->post(route('recipient.reply', $greeting->slug), ['message' => 'Terima kasih!'])->assertForbidden();

        $this->post(route('recipient.open', $greeting->slug));
        $this->post(route('recipient.reply', $greeting->slug), ['message' => 'Terima kasih!'])->assertSessionHasNoErrors();

        $this->assertSame('Terima kasih!', $greeting->replies()->sole()->message);
    }

    public function test_unpublished_greeting_is_not_reachable_by_recipients(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['slug' => 'belumterbit']);

        $this->get(route('recipient.show', $greeting->slug))->assertNotFound();
    }

    private function publishedGreetingWithGate(): Greeting
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create(['content' => $this->completeContent()]);
        Gate::factory()->for($greeting)->create();

        return $greeting;
    }
}
