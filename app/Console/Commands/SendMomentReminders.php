<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Notifications\MomentReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kartukita:send-reminders')]
#[Description('Kirim pengingat momen orang tersayang (H-n dan hari-H).')]
class SendMomentReminders extends Command
{
    public function handle(): int
    {
        $today = CarbonImmutable::today();
        $sent = 0;

        Reminder::query()
            ->with('user')
            ->where(fn ($query) => $query->whereNull('last_notified_on')->orWhere('last_notified_on', '<', $today))
            ->lazyById()
            ->each(function (Reminder $reminder) use ($today, &$sent): void {
                $daysLeft = $reminder->daysUntil($today);

                if ($daysLeft !== $reminder->remind_days_before && $daysLeft !== 0) {
                    return;
                }

                $reminder->user->notify(new MomentReminder($reminder, $daysLeft));
                $reminder->update(['last_notified_on' => $today]);
                $sent++;
            });

        $this->info("{$sent} pengingat dikirim.");

        return self::SUCCESS;
    }
}
