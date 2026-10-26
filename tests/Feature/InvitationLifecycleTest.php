<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\GreetingType;
use App\Enums\OrderStatus;
use App\Exceptions\GreetingLockedException;
use App\Models\Greeting;
use App\Models\InvitationEvent;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Undangan: dibuat dari template undangan, wajib punya acara sebelum dibayar, boleh direvisi
 * sampai beberapa hari setelah acara (snapshot baru, link tetap), lalu ditutup setelah masa aktif.
 */
class InvitationLifecycleTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_draft_from_invitation_template_becomes_invitation_with_default_settings(): void
    {
        $template = $this->createInvitationTemplate();

        $this->post(route('studio.store', $template))->assertRedirect();

        $greeting = Greeting::sole();
        $this->assertSame(GreetingType::Undangan, $greeting->type);
        $this->assertSame(Greeting::INVITATION_RECIPIENT_PLACEHOLDER, $greeting->recipient_name);
        $this->assertTrue($greeting->invitation->rsvp_enabled);
        $this->assertSame(2, $greeting->invitation->default_guest_quota);
    }

    public function test_invitation_without_event_cannot_be_ordered(): void
    {
        $greeting = Greeting::factory()->invitation()->for($this->createInvitationTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertSessionHasErrors(['greeting' => 'Lengkapi dulu: Detail acara.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_invitation_with_event_and_host_name_can_be_ordered(): void
    {
        $greeting = Greeting::factory()->invitation()->for($this->createInvitationTemplate())->create(['content' => $this->completeContent()]);
        InvitationEvent::factory()->for($greeting)->create();

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_revision_creates_new_snapshot_and_keeps_the_same_link(): void
    {
        $greeting = $this->publishedInvitation();
        $slug = $greeting->slug;

        $this->actingAs($greeting->user)
            ->put(route('studio.revise', $greeting), [
                'sender_name' => 'Raka & Dinda Pratama',
                'content' => ['components' => ['surat' => ['body' => 'Dengan memohon rahmat Allah SWT.']]],
            ])
            ->assertSessionHasNoErrors();

        $greeting->refresh();
        $this->assertSame($slug, $greeting->slug);
        $this->assertSame(2, $greeting->snapshots()->count());
        $this->assertSame(2, $greeting->snapshot->revision);
        $this->assertSame('Raka & Dinda Pratama', $greeting->snapshot->sender_name);
        $this->assertSame('Dengan memohon rahmat Allah SWT.', $greeting->snapshot->content['components']['surat']['body']);
        $this->assertSame('Semoga panjang umur.', $greeting->snapshots()->where('revision', 1)->value('content')['components']['surat']['body']);
    }

    public function test_guests_see_the_latest_revision(): void
    {
        $greeting = $this->publishedInvitation();
        $this->actingAs($greeting->user)->put(route('studio.revise', $greeting), ['sender_name' => 'Nama Baru']);
        auth()->logout();

        $this->post(route('recipient.open', $greeting->slug));

        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page->where('greeting.base.sender_name', 'Nama Baru'));
    }

    public function test_studio_opens_published_invitation_in_revision_mode(): void
    {
        $greeting = $this->publishedInvitation();

        $this->actingAs($greeting->user)
            ->get(route('studio.edit', $greeting))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Studio/Edit')
                ->where('revision.revisable_until', $greeting->revisableUntil()->toIso8601String()));
    }

    public function test_studio_redirects_to_result_once_revision_period_is_over(): void
    {
        $greeting = $this->publishedInvitation(eventFinishedDaysAgo: config('kartukita.invitation.revision_grace_days') + 1);

        $this->actingAs($greeting->user)
            ->get(route('studio.edit', $greeting))
            ->assertRedirect(route('result.show', $greeting));
    }

    public function test_photos_can_be_uploaded_during_revision_but_not_after(): void
    {
        Storage::fake('local');
        $revisable = $this->publishedInvitation();
        $closed = $this->publishedInvitation(eventFinishedDaysAgo: config('kartukita.invitation.revision_grace_days') + 1, template: $revisable->template);

        $this->actingAs($revisable->user)
            ->post(route('studio.media.store', $revisable), ['file' => UploadedFile::fake()->image('mempelai.jpg'), 'kind' => 'image'])
            ->assertCreated();

        $this->actingAs($closed->user)
            ->post(route('studio.media.store', $closed), ['file' => UploadedFile::fake()->image('mempelai.jpg'), 'kind' => 'image'])
            ->assertSessionHas('error');

        $this->assertSame(0, $closed->media()->count());
    }

    public function test_result_page_of_invitation_points_to_guest_management(): void
    {
        $greeting = $this->publishedInvitation();

        $this->actingAs($greeting->user)
            ->get(route('result.show', $greeting))
            ->assertInertia(fn (Assert $page) => $page
                ->where('greeting.type', 'undangan')
                ->where('greeting.manage_url', route('invitations.show', $greeting))
                ->where('greeting.whatsapp_text', fn (string $text) => str_contains($text, 'Raka & Dinda') && ! str_contains($text, 'Tamu Undangan')));
    }

    public function test_revision_is_rejected_after_the_grace_period(): void
    {
        $greeting = $this->publishedInvitation(eventFinishedDaysAgo: config('kartukita.invitation.revision_grace_days') + 1);

        $this->actingAs($greeting->user)
            ->put(route('studio.revise', $greeting), ['sender_name' => 'Terlambat'])
            ->assertSessionHasErrors(['greeting' => 'Masa revisi kartu ini sudah berakhir.']);

        $this->assertSame(1, $greeting->snapshots()->count());
    }

    public function test_revision_cannot_remove_required_content(): void
    {
        $greeting = $this->publishedInvitation();

        $this->actingAs($greeting->user)
            ->put(route('studio.revise', $greeting), ['sender_name' => ''])
            ->assertSessionHasErrors(['greeting' => 'Lengkapi dulu: Nama mempelai / tuan rumah.']);
    }

    public function test_revisable_invitation_still_cannot_switch_template(): void
    {
        $greeting = $this->publishedInvitation();

        $this->expectException(GreetingLockedException::class);

        $greeting->update(['template_id' => Template::factory()->invitation()->create()->id]);
    }

    public function test_shared_invitation_keeps_its_link_and_template_through_every_edit_route(): void
    {
        $greeting = $this->publishedInvitation();
        $otherTemplate = Template::factory()->invitation()->create();
        [$slug, $templateId] = [$greeting->slug, $greeting->template_id];
        $this->actingAs($greeting->user);

        // Revisi isi: kolom link & template yang ikut dikirim diabaikan, isinya tetap tersimpan.
        $this->put(route('studio.revise', $greeting), ['sender_name' => 'Raka & Dinda Baru', 'slug' => 'link-baru', 'template_id' => $otherTemplate->id])
            ->assertSessionHasNoErrors();
        // Simpan draf (autosave), ganti gaya, dan checkout ulang ditolak untuk undangan yang sudah terbit.
        $this->put(route('studio.update', $greeting), ['sender_name' => 'Lewat autosave', 'template_id' => $otherTemplate->id])->assertSessionHas('error');
        $this->get(route('studio.style', $greeting))->assertNotFound();
        $this->post(route('checkout.store', $greeting), ['confirm_data' => true, 'slug' => 'link-baru']);

        $greeting->refresh();
        $this->assertSame($slug, $greeting->slug);
        $this->assertSame($templateId, $greeting->template_id);
        $this->assertSame($templateId, $greeting->snapshot->template_id);
        $this->assertSame('Raka & Dinda Baru', $greeting->snapshot->sender_name);
        $this->assertDatabaseCount('orders', 0);
        $this->get(route('recipient.show', $slug))->assertOk();
    }

    public function test_events_edited_after_sharing_show_up_on_the_guest_page(): void
    {
        $greeting = $this->publishedInvitation();
        $event = $greeting->invitationEvents()->sole();

        $this->actingAs($greeting->user)
            ->put(route('invitations.events.update', [$greeting, $event]), [
                'name' => 'Resepsi', 'starts_at' => now()->addMonth()->format('Y-m-d H:i'), 'timezone' => 'Asia/Jakarta', 'venue_name' => 'Gedung Baru',
            ])
            ->assertSessionHasNoErrors();
        auth()->logout();

        $this->post(route('recipient.open', $greeting->slug));
        $this->get(route('recipient.show', $greeting->slug))
            ->assertInertia(fn (Assert $page) => $page->where('greeting.invitation.events.0.venue_name', 'Gedung Baru'));
    }

    public function test_owner_cannot_revise_a_published_regular_greeting(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->published()->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)
            ->put(route('studio.revise', $greeting), ['sender_name' => 'Lain'])
            ->assertSessionHasErrors(['greeting' => 'Masa revisi kartu ini sudah berakhir.']);

        $this->assertSame(1, $greeting->snapshots()->count());
    }

    public function test_guest_page_returns_410_after_the_active_period(): void
    {
        $greeting = $this->publishedInvitation(eventFinishedDaysAgo: config('kartukita.invitation.active_days_after_event') + 1);

        $this->get(route('recipient.show', $greeting->slug))->assertStatus(410);
    }

    public function test_duplicating_invitation_copies_events_and_settings_but_not_guests(): void
    {
        $greeting = $this->publishedInvitation();
        $greeting->invitation->update(['default_guest_quota' => 4]);
        $greeting->guests()->create(['code' => 'abc12345', 'name' => 'Budi']);

        $this->actingAs($greeting->user)->post(route('dashboard.greetings.duplicate', $greeting));

        $copy = Greeting::query()->whereKeyNot($greeting->id)->sole();
        $this->assertSame(GreetingType::Undangan, $copy->type);
        $this->assertSame(4, $copy->invitation->default_guest_quota);
        $this->assertSame(1, $copy->invitationEvents()->count());
        $this->assertSame(0, $copy->guests()->count());
    }

    public function test_studio_receives_live_invitation_data_for_the_player(): void
    {
        $greeting = Greeting::factory()->invitation()->for($this->createInvitationTemplate())->create();
        InvitationEvent::factory()->for($greeting)->create(['name' => 'Akad Nikah']);

        $this->actingAs($greeting->user)
            ->get(route('studio.edit', $greeting))
            ->assertInertia(fn (Assert $page) => $page
                ->where('greeting.invitation.events.0.name', 'Akad Nikah')
                ->where('greeting.invitation.guest', null));
    }

    public function test_catalog_demo_of_invitation_template_uses_sample_events_with_future_dates(): void
    {
        $template = $this->createInvitationTemplate();
        $template->update(['sample_content' => [
            'base' => ['sender_name' => 'Raka & Dinda'],
            'invitation' => ['events' => [['name' => 'Resepsi', 'starts_at' => '+45 days 11:00', 'timezone' => 'Asia/Makassar', 'venue_name' => 'Gedung']]],
        ]]);

        $this->get(route('catalog.demo', $template))
            ->assertInertia(fn (Assert $page) => $page
                ->where('greeting.invitation.events.0.name', 'Resepsi')
                ->where('greeting.invitation.events.0.timezone_label', 'WITA')
                ->where('greeting.invitation.events.0.starts_at', fn (string $startsAt) => now()->lt($startsAt)));
    }

    public function test_regular_greeting_payload_has_no_invitation_data(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create();

        $this->actingAs($greeting->user)
            ->get(route('studio.edit', $greeting))
            ->assertInertia(fn (Assert $page) => $page->where('greeting.invitation', null));
    }

    public function test_admin_publishes_own_invitation_without_payment(): void
    {
        $greeting = Greeting::factory()->invitation()->for($this->createInvitationTemplate())->for(User::factory()->admin())->create(['content' => $this->completeContent()]);
        InvitationEvent::factory()->for($greeting)->create();

        $this->actingAs($greeting->user)
            ->post(route('checkout.complimentary', $greeting), ['confirm_data' => true])
            ->assertRedirect(route('result.show', $greeting));

        $order = $greeting->orders()->sole();
        $this->assertSame(OrderStatus::Lunas, $order->status);
        $this->assertSame(0, $order->amount);
        $this->assertSame(GreetingStatus::Terbit, $greeting->refresh()->status);
        $this->assertNotNull($greeting->snapshot);
    }

    public function test_non_admin_cannot_publish_invitation_without_payment(): void
    {
        $greeting = Greeting::factory()->invitation()->for($this->createInvitationTemplate())->create(['content' => $this->completeContent()]);
        InvitationEvent::factory()->for($greeting)->create();

        $this->actingAs($greeting->user)
            ->post(route('checkout.complimentary', $greeting), ['confirm_data' => true])
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_invitation_stays_revisable_after_revision_period(): void
    {
        $greeting = $this->publishedInvitation(eventFinishedDaysAgo: config('kartukita.invitation.revision_grace_days') + 1);
        $greeting->user->forceFill(['is_admin' => true])->save();

        $this->actingAs($greeting->user)
            ->get(route('studio.edit', $greeting))
            ->assertInertia(fn (Assert $page) => $page->component('Studio/Edit')->where('revision.revisable_until', null));

        $this->actingAs($greeting->user)
            ->put(route('studio.revise', $greeting), ['sender_name' => 'Nama Baru'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $greeting->refresh()->snapshot->sender_name);
    }

    private function publishedInvitation(?int $eventFinishedDaysAgo = null, ?Template $template = null): Greeting
    {
        $greeting = Greeting::factory()->invitation()->for($template ?? $this->createInvitationTemplate())->published()->create(['content' => $this->completeContent()]);
        $event = InvitationEvent::factory()->for($greeting);
        ($eventFinishedDaysAgo === null ? $event : $event->finishedDaysAgo($eventFinishedDaysAgo))->create();

        return $greeting;
    }
}
