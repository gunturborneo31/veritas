<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Enums\GreetingType;
use App\Models\Greeting;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDraftGreeting
{
    /**
     * Draf dibuat tanpa login; pemiliknya akun (jika ada) atau hash token draf anonim.
     * Jenis produk mengikuti template; undangan langsung mendapat pengaturan bawaan.
     */
    public function handle(Template $template, ?User $user, ?string $draftTokenHash, ?int $referredByGreetingId = null): Greeting
    {
        return DB::transaction(function () use ($template, $user, $draftTokenHash, $referredByGreetingId): Greeting {
            $isInvitation = $template->type === GreetingType::Undangan;

            $greeting = Greeting::create([
                'template_id' => $template->id,
                'type' => $template->type,
                'user_id' => $user?->id,
                'draft_token_hash' => $user === null ? $draftTokenHash : null,
                'referred_by_greeting_id' => $referredByGreetingId,
                'status' => GreetingStatus::Draf,
                'recipient_name' => $isInvitation ? Greeting::INVITATION_RECIPIENT_PLACEHOLDER : null,
                'occasion' => $template->sample_content['base']['occasion'] ?? null,
                'content' => ['components' => []],
                'last_edited_at' => now(),
            ]);

            if ($isInvitation) {
                $greeting->invitation()->create();
            }

            return $greeting;
        });
    }
}
