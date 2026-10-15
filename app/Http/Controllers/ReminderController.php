<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReminderRequest;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pengingat momen orang tersayang, ditampilkan di Dashboard.
 */
class ReminderController extends Controller
{
    public function store(StoreReminderRequest $request): RedirectResponse
    {
        $request->user()->reminders()->create($request->validated());

        return back()->with('success', 'Pengingat disimpan. Kami kabari sebelum harinya tiba ⏰');
    }

    public function destroy(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->user_id === $request->user()->id, 404);

        $reminder->delete();

        return back()->with('success', 'Pengingat dihapus.');
    }
}
