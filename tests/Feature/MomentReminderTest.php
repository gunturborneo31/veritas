<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\MomentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MomentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_saves_a_reminder_and_invalid_dates_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('reminders.store'), ['person_name' => 'Ibu', 'occasion' => 'Ulang Tahun', 'month' => 2, 'day' => 30, 'remind_days_before' => 7])
            ->assertSessionHasErrors(['day' => 'Tanggal tidak valid untuk bulan tersebut.']);

        $this->actingAs($user)
            ->post(route('reminders.store'), ['person_name' => 'Ibu', 'occasion' => 'Ulang Tahun', 'month' => 12, 'day' => 22, 'remind_days_before' => 7])
            ->assertSessionHasNoErrors();

        $this->assertSame('Ibu', $user->reminders()->sole()->person_name);
    }

    public function test_user_cannot_delete_someone_elses_reminder(): void
    {
        $reminder = Reminder::factory()->create();

        $this->actingAs(User::factory()->create())->delete(route('reminders.destroy', $reminder))->assertNotFound();
        $this->assertModelExists($reminder);
    }

    public function test_command_reminds_on_lead_day_and_on_the_day_only_once(): void
    {
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 12, 15)->startOfDay());
        $reminder = Reminder::factory()->create(['month' => 12, 'day' => 22, 'remind_days_before' => 7]);
        Reminder::factory()->create(['month' => 12, 'day' => 25, 'remind_days_before' => 7]);

        $this->artisan('kartukita:send-reminders')->assertSuccessful();
        $this->artisan('kartukita:send-reminders')->assertSuccessful();

        Notification::assertSentTimes(MomentReminder::class, 1);
        Notification::assertSentTo($reminder->user, MomentReminder::class, fn (MomentReminder $notification): bool => $notification->daysLeft === 7);

        $this->travelTo(now()->setDate(2026, 12, 22)->startOfDay());
        $this->artisan('kartukita:send-reminders')->assertSuccessful();

        Notification::assertSentTo($reminder->user, MomentReminder::class, fn (MomentReminder $notification): bool => $notification->daysLeft === 0);
    }

    public function test_next_occurrence_rolls_over_to_next_year_and_handles_leap_day(): void
    {
        $this->travelTo(now()->setDate(2027, 3, 1));

        $leapDay = Reminder::factory()->make(['month' => 2, 'day' => 29]);

        $this->assertSame('2028-02-29', $leapDay->nextOccurrence()->toDateString());

        $this->travelTo(now()->setDate(2027, 1, 1));
        $this->assertSame('2027-02-28', $leapDay->nextOccurrence()->toDateString());
    }
}
