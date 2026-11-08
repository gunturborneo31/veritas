<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Greeting;
use App\Models\Reply;
use App\Models\Template;
use App\Notifications\GreetingOpened;
use App\Notifications\GreetingReacted;
use App\Notifications\GreetingReplied;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Kirim terjadwal, reaksi, jawaban "Satu Pertanyaan", dan notifikasi ke pengirim.
 */
class RecipientEngagementTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private const BROWSER_UA = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/128 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('User-Agent', self::BROWSER_UA);
    }

    public function test_scheduled_greeting_cannot_be_opened_before_its_time(): void
    {
        $greeting = $this->publishedGreeting(['opens_at' => now()->addHours(3)]);

        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page->component('Recipient/Gate')->where('opens_at', $greeting->opens_at->toIso8601String()));

        $this->post(route('recipient.open', $greeting->slug))
            ->assertSessionHasErrors(['answer' => 'Sabar ya, kejutannya belum waktunya dibuka.']);

        $this->travel(4)->hours();

        $this->post(route('recipient.open', $greeting->slug))->assertSessionHasNoErrors();
    }

    public function test_sender_is_notified_only_on_the_first_open(): void
    {
        Notification::fake();
        $greeting = $this->publishedGreeting();

        $this->post(route('recipient.open', $greeting->slug));
        $this->post(route('recipient.open', $greeting->slug));

        Notification::assertSentToTimes($greeting->user, GreetingOpened::class, 1);
    }

    public function test_emoji_reaction_is_stored_once_per_session_and_notifies_sender(): void
    {
        Notification::fake();
        $greeting = $this->publishedGreeting();
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'emoji', 'value' => '🥺'])->assertSessionHasNoErrors();
        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'emoji', 'value' => '🥺']);

        $this->assertSame(1, $greeting->reactions()->count());
        Notification::assertSentTo($greeting->user, GreetingReacted::class);
    }

    public function test_reaction_requires_opened_greeting_and_allowed_emoji(): void
    {
        $greeting = $this->publishedGreeting();

        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'emoji', 'value' => '🥺'])->assertForbidden();

        $this->post(route('recipient.open', $greeting->slug));
        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'emoji', 'value' => '💩'])->assertSessionHasErrors('value');
    }

    public function test_question_answer_is_recorded_once_for_a_real_question_slot(): void
    {
        Notification::fake();
        $template = Template::factory()
            ->withComponents(['tanya' => Component::factory()->create([
                'key' => 'tanya_jawab',
                'schema' => [['name' => 'question', 'type' => 'text', 'label' => 'Pertanyaan', 'required' => true]],
            ])])
            ->create();
        $greeting = Greeting::factory()->for($template)->published()->create(['content' => ['components' => ['tanya' => ['question' => 'Mau?']]]]);
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'answer', 'slot_key' => 'bukan_slot', 'value' => 'Mau!'])
            ->assertSessionHasErrors('slot_key');
        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'answer', 'slot_key' => 'tanya', 'value' => 'Mau!']);
        $this->post(route('recipient.react', $greeting->slug), ['kind' => 'answer', 'slot_key' => 'tanya', 'value' => 'Mau lagi']);

        $this->assertSame(['Mau!'], $greeting->reactions()->pluck('value')->all());
        Notification::assertSentTo($greeting->user, GreetingReacted::class, fn (GreetingReacted $notification): bool => $notification->via($greeting->user) === ['database', 'mail']);
    }

    public function test_reply_notifies_sender(): void
    {
        Notification::fake();
        $greeting = $this->publishedGreeting();
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.reply', $greeting->slug), ['message' => 'Makasih banyak!']);

        Notification::assertSentTo($greeting->user, GreetingReplied::class);
    }

    public function test_reply_can_carry_a_photo_that_the_sender_sees_on_the_dashboard(): void
    {
        Storage::fake(config('kartukita.media.disk'));
        $greeting = $this->publishedGreeting();
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.reply', $greeting->slug), [
            'message' => 'Ini foto kita!',
            'attachment' => UploadedFile::fake()->image('kita.jpg', 800, 600),
        ])->assertSessionHasNoErrors();

        $reply = Reply::sole();
        $this->assertSame(Reply::ATTACHMENT_IMAGE, $reply->attachment_type);
        Storage::disk(config('kartukita.media.disk'))->assertExists($reply->attachment_path);

        $this->actingAs($greeting->user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('greetings.0.replies.0.attachment_type', 'image')
            ->where('greetings.0.replies.0.attachment_url', fn (?string $url) => $url !== null));
    }

    public function test_reply_can_be_only_a_video_without_text(): void
    {
        Storage::fake(config('kartukita.media.disk'));
        $greeting = $this->publishedGreeting();
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.reply', $greeting->slug), [
            'attachment' => UploadedFile::fake()->create('balasan.mp4', 2048, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $reply = Reply::sole();
        $this->assertNull($reply->message);
        $this->assertSame(Reply::ATTACHMENT_VIDEO, $reply->attachment_type);
    }

    public function test_empty_reply_and_unsupported_files_are_rejected(): void
    {
        Storage::fake(config('kartukita.media.disk'));
        $greeting = $this->publishedGreeting();
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.reply', $greeting->slug), ['message' => ''])->assertSessionHasErrors('message');
        $this->post(route('recipient.reply', $greeting->slug), [
            'attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('attachment');

        $this->assertSame(0, Reply::count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedGreeting(array $attributes = []): Greeting
    {
        return Greeting::factory()
            ->for($this->createTemplate())
            ->published()
            ->create(['content' => $this->completeContent(), ...$attributes]);
    }
}
