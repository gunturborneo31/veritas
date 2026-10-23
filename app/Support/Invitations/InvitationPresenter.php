<?php

namespace App\Support\Invitations;

use App\Enums\RsvpAttendance;
use App\Models\GiftAccount;
use App\Models\Greeting;
use App\Models\Guest;
use App\Models\InvitationEvent;
use App\Models\Rsvp;
use App\Models\Template;
use App\Models\Wish;

/**
 * Data undangan yang hidup (tidak ikut snapshot): acara, amplop digital, RSVP, dan ucapan & doa.
 * Satu versi untuk halaman tamu, satu versi untuk halaman kelola milik tuan rumah.
 */
class InvitationPresenter
{
    /**
     * Jumlah ucapan & doa terbaru yang ikut dimuat di halaman tamu.
     */
    public const WISHES_ON_PAGE = 50;

    /**
     * @return array<string, mixed>
     */
    public function forGuest(Greeting $greeting, ?Guest $guest, ?Rsvp $rsvp): array
    {
        $greeting->loadMissing('invitation', 'invitationEvents', 'giftAccounts');
        $invitation = $greeting->invitation;
        // Halaman tamu membaca nama dari snapshot; Studio & pratinjau dari draf.
        $title = (string) ($greeting->snapshot ?? $greeting)->sender_name;

        return [
            'guest' => $guest === null ? null : ['name' => $guest->name, 'quota' => $guest->quota],
            'events' => $greeting->invitationEvents
                ->map(fn (InvitationEvent $event): array => $event->toPublicArray($title))
                ->values(),
            'gifts' => $invitation->gifts_enabled
                ? $greeting->giftAccounts->map(fn (GiftAccount $account): array => $account->toPublicArray())->values()
                : [],
            'rsvp' => [
                'enabled' => $invitation->rsvp_enabled,
                'is_open' => $invitation->acceptsRsvp(),
                'deadline' => $invitation->rsvp_deadline?->toIso8601String(),
                'max_party_size' => $guest?->quota ?? $invitation->default_guest_quota,
                'response' => $rsvp === null ? null : [
                    'attendance' => $rsvp->attendance->value,
                    'party_size' => $rsvp->party_size,
                ],
            ],
            'wishes' => [
                'enabled' => $invitation->wishes_enabled,
                'items' => $invitation->wishes_enabled
                    ? $greeting->wishes()->visible()->latest()->limit(self::WISHES_ON_PAGE)->get()
                        ->map(fn (Wish $wish): array => [
                            'name' => $wish->name,
                            'message' => $wish->message,
                            'created_at' => $wish->created_at->toIso8601String(),
                        ])
                    : [],
            ],
        ];
    }

    /**
     * Data contoh untuk demo katalog dari `sample_content.invitation`. Tanggal boleh relatif
     * (mis. "+45 days 08:00") agar hitung mundur demo tidak pernah kedaluwarsa.
     *
     * @return array<string, mixed>
     */
    public function forSample(Template $template): array
    {
        $sample = $template->sample_content['invitation'] ?? [];
        $title = (string) ($template->sample_content['base']['sender_name'] ?? '');
        $toTime = fn (?string $value): ?string => $value === null ? null : (str_starts_with($value, '+') ? now()->modify($value)->toDateTimeString() : $value);

        return [
            'guest' => null,
            'events' => collect($sample['events'] ?? [])
                ->map(fn (array $event): array => (new InvitationEvent([
                    ...$event,
                    'starts_at' => $toTime($event['starts_at']),
                    'ends_at' => $toTime($event['ends_at'] ?? null),
                ]))->toPublicArray($title))
                ->values(),
            'gifts' => collect($sample['gifts'] ?? [])
                ->map(fn (array $gift): array => (new GiftAccount($gift))->toPublicArray())
                ->values(),
            'rsvp' => ['enabled' => true, 'is_open' => true, 'deadline' => null, 'max_party_size' => 2, 'response' => null],
            'wishes' => [
                'enabled' => true,
                'items' => collect($sample['wishes'] ?? [])
                    ->map(fn (array $wish, int $index): array => [...$wish, 'created_at' => now()->subHours(($index + 1) * 5)->toIso8601String()])
                    ->values(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forOwner(Greeting $greeting): array
    {
        $greeting->loadMissing('template', 'invitation', 'invitationEvents', 'giftAccounts');
        $invitation = $greeting->invitation;
        $guests = $greeting->guests()->with('rsvp')->orderBy('name')->get();
        $guests->each(fn (Guest $guest) => $guest->setRelation('greeting', $greeting));
        $rsvps = $greeting->rsvps()->get();
        $attending = $rsvps->where('attendance', RsvpAttendance::Hadir);

        return [
            'id' => $greeting->public_id,
            'status' => $greeting->status->value,
            'status_label' => $greeting->status->label(),
            'is_draft' => $greeting->isEditable(),
            'has_expired' => $greeting->hasExpired(),
            'host_name' => $greeting->sender_name,
            'occasion' => $greeting->occasion,
            'template_name' => $greeting->template->name,
            'url' => $greeting->slug === null ? null : route('recipient.show', $greeting->slug),
            'is_revisable' => $greeting->isRevisable(),
            'revisable_until' => $greeting->revisableUntil()?->toIso8601String(),
            'active_until' => $greeting->activeUntil()?->toIso8601String(),
            'settings' => [
                'rsvp_enabled' => $invitation->rsvp_enabled,
                'rsvp_deadline' => $invitation->rsvp_deadline?->format('Y-m-d\TH:i'),
                'wishes_enabled' => $invitation->wishes_enabled,
                'gifts_enabled' => $invitation->gifts_enabled,
                'default_guest_quota' => $invitation->default_guest_quota,
                'broadcast_message' => $invitation->broadcast_message ?: config('kartukita.invitation.default_broadcast_message'),
            ],
            'events' => $greeting->invitationEvents->map(fn (InvitationEvent $event): array => $event->toEditableArray())->values(),
            'gifts' => $greeting->giftAccounts->map(fn (GiftAccount $account): array => $account->toPublicArray())->values(),
            'guests' => $guests->map(fn (Guest $guest): array => [
                'id' => $guest->id,
                'code' => $guest->code,
                'name' => $guest->name,
                'phone' => $guest->phone,
                'group' => $guest->group,
                'quota' => $guest->quota,
                'url' => $guest->personalUrl(),
                'whatsapp_url' => $guest->whatsappUrl($invitation),
                'qr_download_url' => $greeting->slug === null ? null : route('invitations.guests.qr', [$greeting, $guest]),
                'invited_at' => $guest->invited_at?->toIso8601String(),
                'opened_at' => $guest->opened_at?->toIso8601String(),
                'checked_in_at' => $guest->checked_in_at?->toIso8601String(),
                'rsvp' => $guest->rsvp === null ? null : [
                    'attendance' => $guest->rsvp->attendance->value,
                    'party_size' => $guest->rsvp->party_size,
                ],
            ])->values(),
            'groups' => $guests->pluck('group')->filter()->unique()->sort()->values(),
            'summary' => [
                'guests' => $guests->count(),
                'invited' => $guests->whereNotNull('invited_at')->count(),
                'opened' => $guests->whereNotNull('opened_at')->count(),
                'responses' => $rsvps->count(),
                'attending' => $attending->count(),
                'attendees' => $attending->sum('party_size'),
                'not_attending' => $rsvps->where('attendance', RsvpAttendance::TidakHadir)->count(),
                'unsure' => $rsvps->where('attendance', RsvpAttendance::Ragu)->count(),
                'awaiting' => $guests->whereNull('rsvp')->count(),
                'checked_in' => $guests->whereNotNull('checked_in_at')->count(),
            ],
            // Pengunjung link umum (tanpa kode tamu) tetap bisa RSVP; ditampilkan terpisah dari daftar tamu.
            'walk_in_rsvps' => $rsvps->whereNull('guest_id')->map(fn (Rsvp $rsvp): array => [
                'name' => $rsvp->name,
                'attendance' => $rsvp->attendance->value,
                'party_size' => $rsvp->party_size,
                'created_at' => $rsvp->created_at->toIso8601String(),
            ])->values(),
            'wishes' => $greeting->wishes()->latest()->get()->map(fn (Wish $wish): array => [
                'id' => $wish->id,
                'name' => $wish->name,
                'message' => $wish->message,
                'is_hidden' => $wish->is_hidden,
                'created_at' => $wish->created_at->toIso8601String(),
            ]),
            'timezones' => config('kartukita.invitation.timezones'),
            'max_guest_quota' => config('kartukita.invitation.max_guest_quota'),
        ];
    }
}
