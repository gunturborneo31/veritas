<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Models\Template;
use App\Models\User;

class CreateDraftGreeting
{
    /**
     * Draf dibuat tanpa login; pemiliknya akun (jika ada) atau hash token draf anonim.
     */
    public function handle(Template $template, ?User $user, ?string $draftTokenHash, ?int $referredByGreetingId = null): Greeting
    {
        return Greeting::create([
            'template_id' => $template->id,
            'user_id' => $user?->id,
            'draft_token_hash' => $user === null ? $draftTokenHash : null,
            'referred_by_greeting_id' => $referredByGreetingId,
            'status' => GreetingStatus::Draf,
            'occasion' => $template->sample_content['base']['occasion'] ?? null,
            'content' => ['components' => []],
            'last_edited_at' => now(),
        ]);
    }
}
