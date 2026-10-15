<?php

namespace App\Http\Middleware;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Loop viral: penerima yang menekan "Balas dengan kejutan" datang dengan `?dari={slug}`.
 * Ucapan asal disimpan di sesi lalu dicatat pada draf baru yang dibuatnya.
 */
class CaptureReferral
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->query('dari');

        if (is_string($slug) && $slug !== '') {
            $greetingId = Greeting::query()
                ->where('slug', $slug)
                ->where('status', GreetingStatus::Terbit)
                ->value('id');

            if ($greetingId !== null) {
                $request->session()->put('referral_greeting_id', $greetingId);
            }
        }

        return $next($request);
    }
}
