<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Exceptions\GreetingLockedException;
use App\Models\Greeting;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Aturan bisnis: tidak ada jalur apa pun yang bisa mengubah isi ucapan setelah dikunci.
 */
class GreetingLockTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_published_greeting_content_cannot_be_changed_at_model_level(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create();

        $this->expectException(GreetingLockedException::class);

        $greeting->update(['content' => ['components' => ['surat' => ['body' => 'diubah']]]]);
    }

    public function test_snapshot_cannot_be_updated_or_deleted(): void
    {
        $snapshot = Greeting::factory()->for($this->createTemplate())->published()->create()->snapshot;

        $this->assertThrows(fn () => $snapshot->update(['recipient_name' => 'lain']), GreetingLockedException::class);
        $this->assertThrows(fn () => $snapshot->delete(), GreetingLockedException::class);
    }

    public function test_status_can_still_move_forward_on_locked_greeting(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->status(GreetingStatus::Terkunci)->create();

        $greeting->update(['status' => GreetingStatus::Terbit]);

        $this->assertSame(GreetingStatus::Terbit, $greeting->refresh()->status);
    }

    public function test_stale_anonymous_drafts_are_pruned_but_account_drafts_are_kept(): void
    {
        $template = $this->createTemplate();
        $stale = Greeting::factory()->for($template)->anonymous(DraftToken::hash('lama'))->create(['last_edited_at' => now()->subDays(31)]);
        $fresh = Greeting::factory()->for($template)->anonymous(DraftToken::hash('baru'))->create(['last_edited_at' => now()->subDays(5)]);
        $owned = Greeting::factory()->for($template)->create(['last_edited_at' => now()->subDays(90)]);

        $this->artisan('model:prune', ['--model' => [Greeting::class]])->assertSuccessful();

        $this->assertModelMissing($stale);
        $this->assertModelExists($fresh);
        $this->assertModelExists($owned);
    }
}
