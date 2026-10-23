<?php

namespace Tests\Feature;

use App\Enums\RsvpAttendance;
use App\Models\GiftAccount;
use App\Models\Greeting;
use App\Models\Guest;
use App\Models\InvitationEvent;
use App\Models\Wish;
use App\Notifications\InvitationRsvpReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman tamu undangan: link pribadi, RSVP, ucapan & doa, dan data acara/amplop.
 */
class InvitationGuestPageTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER_UA = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/128 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('User-Agent', self::BROWSER_UA);
    }

    public function test_personal_link_shows_guest_name_on_cover(): void
    {
        $greeting = $this->publishedInvitation();
        $guest = Guest::factory()->for($greeting)->create(['name' => 'Bapak Budi']);

        $this->get(route('recipient.show', ['slug' => $greeting->slug, 'tamu' => $guest->code]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recipient/Gate')
                ->where('type', 'undangan')
                ->where('recipient_name', 'Bapak Budi'));
    }

    public function test_unknown_guest_code_falls_back_to_generic_recipient(): void
    {
        $greeting = $this->publishedInvitation();

        $this->get(route('recipient.show', ['slug' => $greeting->slug, 'tamu' => 'ngarang1']))
            ->assertInertia(fn (Assert $page) => $page->where('recipient_name', Greeting::INVITATION_RECIPIENT_PLACEHOLDER));
    }

    public function test_opening_invitation_marks_guest_as_opened(): void
    {
        $greeting = $this->publishedInvitation();
        $guest = Guest::factory()->for($greeting)->create();

        $this->visitAsGuest($greeting, $guest);

        $this->assertNotNull($guest->refresh()->opened_at);
    }

    public function test_opened_invitation_includes_events_gifts_and_visible_wishes(): void
    {
        $greeting = $this->publishedInvitation();
        GiftAccount::factory()->for($greeting)->create(['provider' => 'Mandiri']);
        Wish::factory()->for($greeting)->create(['message' => 'Barakallah']);
        Wish::factory()->for($greeting)->hidden()->create(['message' => 'Spam']);

        $this->post(route('recipient.open', $greeting->slug));

        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recipient/Play')
                ->where('greeting.invitation.events.0.name', 'Resepsi')
                ->where('greeting.invitation.gifts.0.provider', 'Mandiri')
                ->has('greeting.invitation.wishes.items', 1)
                ->where('greeting.invitation.wishes.items.0.message', 'Barakallah'));
    }

    public function test_gifts_are_hidden_when_disabled(): void
    {
        $greeting = $this->publishedInvitation();
        $greeting->invitation->update(['gifts_enabled' => false]);
        GiftAccount::factory()->for($greeting)->create();

        $this->post(route('recipient.open', $greeting->slug));

        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page->has('greeting.invitation.gifts', 0));
    }

    public function test_guest_rsvp_is_updated_not_duplicated_and_owner_notified_once(): void
    {
        Notification::fake();
        $greeting = $this->publishedInvitation();
        $guest = Guest::factory()->for($greeting)->create(['name' => 'Budi', 'quota' => 3]);
        $this->visitAsGuest($greeting, $guest);

        $this->post(route('recipient.rsvp', $greeting->slug), ['attendance' => 'hadir', 'party_size' => 3])->assertSessionHasNoErrors();
        $this->post(route('recipient.rsvp', $greeting->slug), ['attendance' => 'tidak_hadir'])->assertSessionHasNoErrors();

        $rsvp = $greeting->rsvps()->sole();
        $this->assertSame($guest->id, $rsvp->guest_id);
        $this->assertSame('Budi', $rsvp->name);
        $this->assertSame(RsvpAttendance::TidakHadir, $rsvp->attendance);
        $this->assertSame(0, $rsvp->party_size);
        Notification::assertSentToTimes($greeting->user, InvitationRsvpReceived::class, 1);
    }

    public function test_rsvp_cannot_exceed_guest_quota(): void
    {
        $greeting = $this->publishedInvitation();
        $guest = Guest::factory()->for($greeting)->create(['quota' => 2]);
        $this->visitAsGuest($greeting, $guest);

        $this->post(route('recipient.rsvp', $greeting->slug), ['attendance' => 'hadir', 'party_size' => 3])
            ->assertSessionHasErrors(['party_size' => 'Undangan ini berlaku untuk maksimal 2 orang.']);

        $this->assertSame(0, $greeting->rsvps()->count());
    }

    public function test_rsvp_is_closed_after_deadline(): void
    {
        $greeting = $this->publishedInvitation();
        $greeting->invitation->update(['rsvp_deadline' => now()->subHour()]);
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.rsvp', $greeting->slug), ['name' => 'Andi', 'attendance' => 'hadir', 'party_size' => 1])
            ->assertSessionHasErrors(['attendance' => 'Konfirmasi kehadiran untuk undangan ini sudah ditutup.']);
    }

    public function test_general_link_rsvp_requires_name_and_is_updated_within_session(): void
    {
        $greeting = $this->publishedInvitation();
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.rsvp', $greeting->slug), ['attendance' => 'hadir', 'party_size' => 1])
            ->assertSessionHasErrors(['name' => 'Tulis namamu dulu, ya.']);

        $this->post(route('recipient.rsvp', $greeting->slug), ['name' => 'Andi', 'attendance' => 'hadir', 'party_size' => 1]);
        $this->post(route('recipient.rsvp', $greeting->slug), ['name' => 'Andi', 'attendance' => 'ragu']);

        $this->assertSame(RsvpAttendance::Ragu, $greeting->rsvps()->sole()->attendance);
    }

    public function test_rsvp_requires_opened_invitation(): void
    {
        $greeting = $this->publishedInvitation();

        $this->post(route('recipient.rsvp', $greeting->slug), ['name' => 'Andi', 'attendance' => 'hadir', 'party_size' => 1])
            ->assertForbidden();
    }

    public function test_wish_from_registered_guest_uses_guest_name(): void
    {
        $greeting = $this->publishedInvitation();
        $guest = Guest::factory()->for($greeting)->create(['name' => 'Bu Sari']);
        $this->visitAsGuest($greeting, $guest);

        $this->post(route('recipient.wish', $greeting->slug), ['name' => 'Orang Lain', 'message' => 'Selamat ya!'])
            ->assertSessionHasNoErrors();

        $wish = $greeting->wishes()->sole();
        $this->assertSame('Bu Sari', $wish->name);
        $this->assertSame($guest->id, $wish->guest_id);
    }

    public function test_wish_is_refused_when_guestbook_is_disabled(): void
    {
        $greeting = $this->publishedInvitation();
        $greeting->invitation->update(['wishes_enabled' => false]);
        $this->post(route('recipient.open', $greeting->slug));

        $this->post(route('recipient.wish', $greeting->slug), ['name' => 'Andi', 'message' => 'Selamat'])->assertForbidden();

        $this->assertSame(0, $greeting->wishes()->count());
    }

    private function publishedInvitation(): Greeting
    {
        $greeting = Greeting::factory()->invitation()->published()->create();
        InvitationEvent::factory()->for($greeting)->create();

        return $greeting;
    }

    private function visitAsGuest(Greeting $greeting, Guest $guest): void
    {
        $this->get(route('recipient.show', ['slug' => $greeting->slug, 'tamu' => $guest->code]));
        $this->post(route('recipient.open', $greeting->slug));
    }
}
