<?php

namespace App\Http\Controllers;

use App\Actions\Recipients\RecordOpenEvent;
use App\Enums\GreetingStatus;
use App\Http\Requests\UnlockGreetingRequest;
use App\Models\Greeting;
use App\Models\Reaction;
use App\Support\Greetings\GreetingPresenter;
use App\Support\Invitations\GuestSession;
use App\Support\Invitations\InvitationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman penerima. Tidak pernah butuh akun, dan selalu membaca dari snapshot.
 *
 * Isi ucapan baru dikirim ke browser setelah penerima menekan "Buka" dan
 * (jika ada gembok) menjawab dengan benar, sehingga gembok benar-benar
 * melindungi isi, bukan sekadar efek visual.
 *
 * Undangan memakai halaman yang sama: link pribadi tamu (`?tamu=kode`) mengganti
 * nama penerima dengan nama tamu, dan data acara/RSVP/amplop ikut dikirim.
 */
class RecipientController extends Controller
{
    public function __construct(private GuestSession $guestSession) {}

    public function show(Request $request, string $slug, GreetingPresenter $presenter, InvitationPresenter $invitationPresenter): Response
    {
        $greeting = $this->findPublished($slug);
        $guest = $greeting->isInvitation() ? $this->guestSession->resolve($request, $greeting) : null;

        if (! $this->isUnlocked($request, $greeting)) {
            return Inertia::render('Recipient/Gate', [
                'slug' => $greeting->slug,
                'type' => $greeting->type->value,
                'recipient_name' => $guest->name ?? $greeting->snapshot->recipient_name,
                'sender_name' => $greeting->snapshot->sender_name,
                'occasion' => $greeting->snapshot->occasion,
                'gate' => $greeting->gate?->toPublicArray(),
                'theme' => $greeting->template->resolvedTheme(),
                'opens_at' => $greeting->isScheduledForLater() ? $greeting->opens_at->toIso8601String() : null,
                'music' => $presenter->backgroundMusic($greeting->snapshot),
            ]);
        }

        return Inertia::render('Recipient/Play', [
            'slug' => $greeting->slug,
            'greeting' => [
                ...$presenter->forSnapshot($greeting->snapshot),
                'invitation' => $greeting->isInvitation()
                    ? $invitationPresenter->forGuest($greeting, $guest, $this->guestSession->rsvp($request, $greeting, $guest))
                    : null,
            ],
            'replied' => (bool) $request->session()->get($this->sessionKey($greeting, 'replied')),
            'reacted' => $request->session()->get($this->sessionKey($greeting, 'reacted'), []),
            'answers' => $request->session()->get($this->sessionKey($greeting, 'answers'), []),
            'reactionEmojis' => Reaction::EMOJIS,
            'videoSent' => (bool) $request->session()->get($this->sessionKey($greeting, 'video_sent')),
        ]);
    }

    public function open(UnlockGreetingRequest $request, string $slug, RecordOpenEvent $recordOpen): RedirectResponse
    {
        $greeting = $this->findPublished($slug);

        if ($greeting->isScheduledForLater()) {
            throw ValidationException::withMessages(['answer' => 'Sabar ya, kejutannya belum waktunya dibuka.']);
        }

        if ($greeting->gate !== null) {
            $limiterKey = "gate:{$greeting->id}:{$request->ip()}";

            if (RateLimiter::tooManyAttempts($limiterKey, config('kartukita.gate.max_attempts_per_minute'))) {
                throw ValidationException::withMessages([
                    'answer' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($limiterKey).' detik.',
                ]);
            }

            if (! $greeting->gate->matches((string) $request->validated('answer'))) {
                RateLimiter::hit($limiterKey);

                throw ValidationException::withMessages(['answer' => 'Kombinasi belum tepat, coba lagi ya!']);
            }

            RateLimiter::clear($limiterKey);
        }

        $request->session()->put($this->sessionKey($greeting, 'unlocked'), true);
        $recordOpen->handle($greeting, $request);

        $guest = $greeting->isInvitation() ? $this->guestSession->guest($request, $greeting) : null;

        if ($guest !== null && $guest->opened_at === null && $request->user()?->id !== $greeting->user_id) {
            $guest->update(['opened_at' => now()]);
        }

        return to_route('recipient.show', $greeting->slug);
    }

    private function findPublished(string $slug): Greeting
    {
        $greeting = Greeting::query()
            ->where('slug', $slug)
            ->with('snapshot.template', 'gate', 'template', 'invitation', 'invitationEvents')
            ->firstOrFail();

        abort_if($greeting->status === GreetingStatus::Arsip, 410, 'Ucapan ini sudah diarsipkan.');
        abort_unless($greeting->status === GreetingStatus::Terbit && $greeting->snapshot !== null, 404);
        abort_if($greeting->isInvitation() && $greeting->hasExpired(), 410, 'Masa aktif undangan ini sudah berakhir.');

        return $greeting;
    }

    private function isUnlocked(Request $request, Greeting $greeting): bool
    {
        return (bool) $request->session()->get($this->sessionKey($greeting, 'unlocked'));
    }

    private function sessionKey(Greeting $greeting, string $suffix): string
    {
        return "recipient.{$greeting->id}.{$suffix}";
    }
}
