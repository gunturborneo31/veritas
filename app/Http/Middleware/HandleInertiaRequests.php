<?php

namespace App\Http\Middleware;

use App\Enums\GreetingStatus;
use App\Http\Controllers\SongController;
use App\Models\Greeting;
use App\Support\Greetings\DraftToken;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'brand' => config('kartukita.brand'),
            'maps' => [
                'tile_url' => config('kartukita.maps.tile_url'),
                'attribution' => config('kartukita.maps.attribution'),
            ],
            'song_search_enabled' => SongController::isSearchEnabled(),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                ],
                'unread_notifications' => fn () => $user?->unreadNotifications()->count() ?? 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'resume_draft' => fn () => $user === null ? $this->guestDraft($request) : null,
        ];
    }

    /**
     * Draf terakhir milik tamu di perangkat ini, supaya ia bisa kembali ke Studio tanpa harus masuk.
     *
     * @return array{url: string, template_name: string, recipient_name: ?string}|null
     */
    private function guestDraft(Request $request): ?array
    {
        $tokenHash = app(DraftToken::class)->hashFromRequest($request);

        if ($tokenHash === null) {
            return null;
        }

        $greeting = Greeting::query()
            ->ownedByDraftToken($tokenHash)
            ->whereIn('status', [GreetingStatus::Draf, GreetingStatus::Pratinjau])
            ->with('template:id,name')
            ->latest('updated_at')
            ->first();

        return $greeting === null ? null : [
            'url' => route('studio.edit', $greeting),
            'template_name' => $greeting->template->name,
            'recipient_name' => $greeting->recipient_name,
        ];
    }
}
