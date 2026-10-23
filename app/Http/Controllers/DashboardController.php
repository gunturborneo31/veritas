<?php

namespace App\Http\Controllers;

use App\Actions\Greetings\DuplicateGreeting;
use App\Enums\OrderStatus;
use App\Enums\PromoKind;
use App\Models\Greeting;
use App\Models\Media;
use App\Models\PromoCode;
use App\Models\Reaction;
use App\Models\Reminder;
use App\Models\Reply;
use App\Support\Greetings\QrCodeGenerator;
use App\Support\Pricing\PromoCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Daftar ucapan, aktivitas terbaru (dibuka/reaksi/balasan), dan pengingat momen.
     */
    public function index(Request $request, PromoCodes $promoCodes, QrCodeGenerator $qr): Response
    {
        $user = $request->user();
        $referral = $promoCodes->referralFor($user);

        $greetings = $user->greetings()
            ->with(['template.category', 'latestOrder', 'replies' => fn ($query) => $query->latest(), 'reactions'])
            ->withCount('openEvents')
            ->withMax('openEvents', 'opened_at')
            ->latest('updated_at')
            ->get();

        return Inertia::render('Dashboard', [
            'greetings' => $greetings->map(fn (Greeting $greeting): array => [
                'id' => $greeting->public_id,
                'status' => $greeting->status->value,
                'status_label' => $greeting->status->label(),
                'type' => $greeting->type->value,
                'manage_url' => $greeting->isInvitation() ? route('invitations.show', $greeting) : null,
                'recipient_name' => $greeting->recipient_name,
                'sender_name' => $greeting->sender_name,
                'occasion' => $greeting->occasion,
                'template' => $greeting->template->toCardArray(),
                'url' => $greeting->slug ? route('recipient.show', $greeting->slug) : null,
                'qr_svg' => $greeting->slug ? $qr->svg(route('recipient.show', $greeting->slug), $greeting->template->resolvedTheme()['accent']) : null,
                'qr_download_url' => $greeting->slug ? route('result.qr', $greeting) : null,
                'opens_at' => $greeting->opens_at?->toIso8601String(),
                'order' => $greeting->latestOrder === null ? null : [
                    'code' => $greeting->latestOrder->code,
                    'status' => $greeting->latestOrder->status->value,
                    'amount' => $greeting->latestOrder->amount,
                    'expires_at' => $greeting->latestOrder->created_at->addHours(config('kartukita.payment.order_expires_hours'))->toIso8601String(),
                ],
                'open_count' => $greeting->open_events_count,
                'last_opened_at' => $greeting->open_events_max_opened_at,
                'last_edited_at' => $greeting->last_edited_at?->toIso8601String(),
                'emoji_reactions' => $greeting->reactions
                    ->where('kind', Reaction::KIND_EMOJI)
                    ->countBy('value')
                    ->map(fn (int $count, string $emoji): array => ['emoji' => $emoji, 'count' => $count])
                    ->values(),
                'answers' => $greeting->reactions->where('kind', Reaction::KIND_ANSWER)->pluck('value')->values(),
                'videos' => $greeting->reactions->where('kind', Reaction::KIND_VIDEO)->map(fn (Reaction $reaction): array => [
                    'id' => $reaction->id,
                    'url' => $reaction->videoUrl(),
                    'created_at' => $reaction->created_at->toIso8601String(),
                ])->values(),
                'replies' => $greeting->replies->map(fn (Reply $reply): array => [
                    'id' => $reply->id,
                    'sender_name' => $reply->sender_name,
                    'message' => $reply->message,
                    'created_at' => $reply->created_at->toIso8601String(),
                ]),
            ]),
            'activities' => $user->notifications()->latest()->limit(15)->get()->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'icon' => $notification->data['icon'] ?? 'notifications',
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'is_unread' => $notification->read_at === null,
                'url' => $notification->data['url'] ?? null,
                'created_at' => $notification->created_at->toIso8601String(),
            ]),
            'reminders' => $user->reminders()->get()
                ->map(fn (Reminder $reminder): array => [
                    'id' => $reminder->id,
                    'person_name' => $reminder->person_name,
                    'occasion' => $reminder->occasion,
                    'month' => $reminder->month,
                    'day' => $reminder->day,
                    'remind_days_before' => $reminder->remind_days_before,
                    'next_date' => $reminder->nextOccurrence()->toDateString(),
                    'days_until' => $reminder->daysUntil(),
                ])
                ->sortBy('days_until')
                ->values(),
            'referral' => [
                'code' => $referral->code,
                'discount' => $referral->describe(),
                'reward' => 'Rp'.number_format(config('kartukita.referral.referrer_reward'), 0, ',', '.'),
                'uses' => $referral->uses_count,
                'share_text' => "Pakai kode {$referral->code} di KartuKita untuk potongan {$referral->describe()} bikin kartu ucapan interaktif! ".route('catalog.index'),
                'vouchers' => PromoCode::query()
                    ->where('restricted_to_user_id', $user->id)
                    ->where('kind', PromoKind::Reward)
                    ->whereColumn('uses_count', '<', 'max_uses')
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                    ->get()
                    ->map(fn (PromoCode $voucher): array => [
                        'code' => $voucher->code,
                        'value' => $voucher->describe(),
                        'ends_at' => $voucher->ends_at?->toIso8601String(),
                    ]),
            ],
        ]);
    }

    /**
     * Salin ucapan untuk penerima lain (dibayar terpisah).
     */
    public function duplicate(Request $request, Greeting $greeting, DuplicateGreeting $duplicateGreeting): RedirectResponse
    {
        abort_unless($greeting->user_id === $request->user()->id, 404);

        $copy = $duplicateGreeting->handle($greeting);

        return to_route('studio.edit', $copy)->with('success', 'Salinan dibuat. Ganti nama penerimanya, ya!');
    }

    /**
     * Hapus draf yang belum dibayar.
     */
    public function destroy(Request $request, Greeting $greeting): RedirectResponse
    {
        abort_unless($greeting->user_id === $request->user()->id, 404);

        $hasPaidOrPendingOrder = $greeting->orders()
            ->whereIn('status', [OrderStatus::MenungguKonfirmasi, OrderStatus::Lunas])
            ->exists();

        abort_if(! $greeting->isEditable() || $hasPaidOrPendingOrder, 403);

        $greeting->media->each(fn (Media $media): bool => Storage::disk($media->disk)->delete($media->path));
        $greeting->orders()->delete();
        $greeting->delete();

        return back()->with('success', 'Draf ucapan telah dihapus.');
    }
}
