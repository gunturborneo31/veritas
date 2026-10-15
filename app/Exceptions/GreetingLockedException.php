<?php

namespace App\Exceptions;

use App\Models\Greeting;
use App\Models\GreetingSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Dilempar setiap kali ada upaya mengubah isi ucapan yang sudah dibekukan.
 */
class GreetingLockedException extends RuntimeException
{
    public static function forGreeting(Greeting $greeting): self
    {
        return new self("Ucapan [{$greeting->public_id}] sudah dikunci dan tidak bisa diubah.");
    }

    public static function forSnapshot(GreetingSnapshot $snapshot): self
    {
        return new self("Snapshot ucapan [{$snapshot->greeting_id}] bersifat permanen.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'Ucapan ini sudah dikunci setelah pembayaran dan tidak bisa diubah lagi.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $message], 409);
        }

        return back()->with('error', $message);
    }
}
