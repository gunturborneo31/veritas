<?php

namespace Tests\Feature;

use App\Models\GiftAccount;
use App\Models\Greeting;
use App\Models\Guest;
use App\Models\InvitationEvent;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tuan rumah mengelola acara, amplop digital, daftar tamu, check-in, dan ucapan & doa.
 */
class InvitationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_time_is_entered_in_venue_timezone_and_stored_in_app_timezone(): void
    {
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.events.store', $greeting), [
                'name' => 'Akad Nikah',
                'starts_at' => '2026-12-12T09:00',
                'ends_at' => '2026-12-12T11:00',
                'timezone' => 'Asia/Makassar',
                'venue_name' => 'Masjid Raya Makassar',
            ])
            ->assertSessionHasNoErrors();

        $event = $greeting->invitationEvents()->sole();
        $this->assertSame('2026-12-12 08:00:00', $event->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-12T09:00', $event->toEditableArray()['starts_at']);
        $this->assertSame('WITA', $event->toPublicArray('Raka & Dinda')['timezone_label']);
    }

    public function test_event_end_must_be_after_start(): void
    {
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.events.store', $greeting), [
                'name' => 'Resepsi',
                'starts_at' => '2026-12-12T19:00',
                'ends_at' => '2026-12-12T18:00',
                'timezone' => 'Asia/Jakarta',
                'venue_name' => 'Gedung',
            ])
            ->assertSessionHasErrors(['ends_at' => 'Jam selesai harus setelah jam mulai.']);
    }

    public function test_published_invitation_must_keep_at_least_one_event(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $event = InvitationEvent::factory()->for($greeting)->create();

        $this->actingAs($greeting->user)
            ->delete(route('invitations.events.destroy', [$greeting, $event]))
            ->assertSessionHasErrors(['event' => 'Undangan yang sudah terbit harus punya minimal satu acara.']);

        $this->assertModelExists($event);
    }

    public function test_event_of_another_invitation_cannot_be_changed_through_own_invitation(): void
    {
        $greeting = Greeting::factory()->invitation()->create();
        $foreignEvent = InvitationEvent::factory()->create();

        $this->actingAs($greeting->user)
            ->delete(route('invitations.events.destroy', [$greeting, $foreignEvent]))
            ->assertNotFound();

        $this->assertModelExists($foreignEvent);
    }

    public function test_other_user_gets_404_for_someone_elses_invitation(): void
    {
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('invitations.show', $greeting))
            ->assertNotFound();
    }

    public function test_owner_sees_guest_list_with_rsvp_summary(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $attending = Guest::factory()->for($greeting)->create(['name' => 'Budi', 'quota' => 3]);
        $attending->rsvp()->create(['greeting_id' => $greeting->id, 'name' => 'Budi', 'attendance' => 'hadir', 'party_size' => 3]);
        Guest::factory()->for($greeting)->create(['name' => 'Citra']);

        $this->actingAs($greeting->user)
            ->get(route('invitations.show', $greeting))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invitation/Manage')
                ->where('invitation.summary.guests', 2)
                ->where('invitation.summary.attending', 1)
                ->where('invitation.summary.attendees', 3)
                ->where('invitation.summary.awaiting', 1)
                ->where('invitation.guests.0.url', route('recipient.show', ['slug' => $greeting->slug, 'tamu' => $attending->code])));
    }

    public function test_address_gift_requires_an_address(): void
    {
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.gifts.store', $greeting), ['kind' => 'alamat', 'account_holder' => 'Dinda'])
            ->assertSessionHasErrors(['address' => 'Alamat pengiriman kado wajib diisi.']);
    }

    public function test_bank_gift_account_is_stored(): void
    {
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.gifts.store', $greeting), [
                'kind' => 'bank', 'provider' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Dinda Ayu',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('1234567890', $greeting->giftAccounts()->sole()->account_number);
    }

    public function test_broadcast_message_must_contain_link_placeholder(): void
    {
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs($greeting->user)
            ->put(route('invitations.settings.update', $greeting), [
                'rsvp_enabled' => true,
                'wishes_enabled' => true,
                'gifts_enabled' => true,
                'default_guest_quota' => 2,
                'broadcast_message' => 'Halo {nama}, datang ya!',
            ])
            ->assertSessionHasErrors(['broadcast_message' => 'Pesan harus memuat {link} agar setiap tamu menerima link undangannya.']);
    }

    public function test_whatsapp_link_is_personalised_per_guest(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $greeting->invitation->update(['broadcast_message' => 'Halo {nama}: {link}']);
        $guest = Guest::factory()->for($greeting)->create(['name' => 'Budi', 'phone' => '0812-3456-7890']);

        $url = $guest->whatsappUrl($greeting->invitation);

        $this->assertSame(
            'https://wa.me/6281234567890?text='.rawurlencode('Halo Budi: '.route('recipient.show', ['slug' => $greeting->slug, 'tamu' => $guest->code])),
            $url,
        );
    }

    public function test_single_guest_gets_default_quota_and_unique_code(): void
    {
        $greeting = Greeting::factory()->invitation()->create();
        $greeting->invitation->update(['default_guest_quota' => 4]);

        $this->actingAs($greeting->user)
            ->post(route('invitations.guests.store', $greeting), ['name' => 'Pak RT'])
            ->assertSessionHasNoErrors();

        $guest = $greeting->guests()->sole();
        $this->assertSame(4, $guest->quota);
        $this->assertSame(Guest::CODE_LENGTH, strlen($guest->code));
    }

    public function test_import_reads_spreadsheet_columns_and_skips_duplicates(): void
    {
        $greeting = Greeting::factory()->invitation()->create();
        Guest::factory()->for($greeting)->create(['name' => 'Budi Santoso']);

        $this->actingAs($greeting->user)
            ->post(route('invitations.guests.import', $greeting), [
                'guests' => "Andi, S.T.\t0812 1111 2222\tKantor\t3\nbudi santoso\n\nCitra;;Keluarga;99\nAndi, S.T.",
            ])
            ->assertSessionHas('success', '2 tamu ditambahkan. 2 baris dilewati (kosong atau nama sudah ada).');

        $andi = $greeting->guests()->where('name', 'Andi, S.T.')->sole();
        $this->assertSame('081211112222', $andi->phone);
        $this->assertSame('Kantor', $andi->group);
        $this->assertSame(3, $andi->quota);
        $this->assertSame(config('kartukita.invitation.max_guest_quota'), $greeting->guests()->where('name', 'Citra')->value('quota'));
    }

    public function test_import_rejects_too_many_rows(): void
    {
        config(['kartukita.invitation.max_import_rows' => 2]);
        $greeting = Greeting::factory()->invitation()->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.guests.import', $greeting), ['guests' => "A\nB\nC"])
            ->assertSessionHasErrors(['guests' => 'Maksimal 2 tamu sekali impor. Bagi menjadi beberapa kali, ya.']);

        $this->assertSame(0, $greeting->guests()->count());
    }

    public function test_marking_guest_as_invited_records_the_time(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $guest = Guest::factory()->for($greeting)->create();

        $this->actingAs($greeting->user)->post(route('invitations.guests.invited', [$greeting, $guest]));

        $this->assertNotNull($guest->refresh()->invited_at);
    }

    public function test_check_in_accepts_scanned_personal_link(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $guest = Guest::factory()->for($greeting)->create(['name' => 'Budi', 'quota' => 2]);

        $this->actingAs($greeting->user)
            ->post(route('invitations.check-in', $greeting), ['code' => route('recipient.show', ['slug' => $greeting->slug, 'tamu' => $guest->code])])
            ->assertSessionHas('success', 'Selamat datang, Budi! Undangan untuk 2 orang.');

        $this->assertNotNull($guest->refresh()->checked_in_at);
    }

    public function test_check_in_warns_when_guest_already_checked_in(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $guest = Guest::factory()->for($greeting)->checkedIn()->create(['name' => 'Budi']);

        $this->actingAs($greeting->user)
            ->post(route('invitations.check-in', $greeting), ['code' => $guest->code])
            ->assertSessionHas('error', "Budi sudah check-in pukul {$guest->checked_in_at->format('H:i')}.");
    }

    public function test_check_in_rejects_code_from_another_invitation(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $foreignGuest = Guest::factory()->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.check-in', $greeting), ['code' => $foreignGuest->code])
            ->assertSessionHasErrors(['code' => 'Tamu tidak ditemukan di undangan ini.']);

        $this->assertNull($foreignGuest->refresh()->checked_in_at);
    }

    public function test_owner_can_hide_a_wish(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        $wish = Wish::factory()->for($greeting)->create();

        $this->actingAs($greeting->user)->patch(route('invitations.wishes.toggle', [$greeting, $wish]));

        $this->assertTrue($wish->refresh()->is_hidden);
    }

    public function test_changes_are_refused_after_the_active_period(): void
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        InvitationEvent::factory()->for($greeting)->finishedDaysAgo(config('kartukita.invitation.active_days_after_event') + 1)->create();

        $this->actingAs($greeting->user)
            ->post(route('invitations.guests.store', $greeting), ['name' => 'Terlambat'])
            ->assertForbidden();

        $this->assertSame(0, $greeting->guests()->count());
    }

    public function test_gift_account_of_another_invitation_is_not_reachable(): void
    {
        $greeting = Greeting::factory()->invitation()->create();
        $foreignAccount = GiftAccount::factory()->create();

        $this->actingAs($greeting->user)
            ->delete(route('invitations.gifts.destroy', [$greeting, $foreignAccount]))
            ->assertNotFound();

        $this->assertModelExists($foreignAccount);
    }
}
