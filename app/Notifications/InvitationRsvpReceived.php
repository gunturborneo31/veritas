<?php

namespace App\Notifications;

use App\Models\Greeting;
use App\Models\Rsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Hanya lewat database (feed aktivitas), tanpa email: undangan bisa menerima ratusan RSVP
 * dan email per RSVP akan membanjiri kotak masuk tuan rumah.
 */
class InvitationRsvpReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Greeting $greeting, public Rsvp $rsvp) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $detail = $this->rsvp->attendance->countsAttendees()
            ? "{$this->rsvp->attendance->label()} ({$this->rsvp->party_size} orang)"
            : $this->rsvp->attendance->label();

        return [
            'icon' => 'event_available',
            'title' => "{$this->rsvp->name} mengonfirmasi kehadiran",
            'body' => $detail,
            'greeting_id' => $this->greeting->public_id,
            'url' => route('invitations.show', $this->greeting),
        ];
    }
}
