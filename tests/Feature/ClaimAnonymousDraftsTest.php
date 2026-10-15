<?php

namespace Tests\Feature;

use App\Models\Greeting;
use App\Models\User;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class ClaimAnonymousDraftsTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_logging_in_moves_device_drafts_into_the_account(): void
    {
        $user = User::factory()->create();
        $template = $this->createTemplate();
        $mine = Greeting::factory()->for($template)->anonymous(DraftToken::hash('perangkat-saya'))->create();
        $others = Greeting::factory()->for($template)->anonymous(DraftToken::hash('perangkat-lain'))->create();

        $this->withCookie('kk_draft', 'perangkat-saya')->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertSame($user->id, $mine->refresh()->user_id);
        $this->assertNull($mine->draft_token_hash);
        $this->assertNull($others->refresh()->user_id);
    }
}
