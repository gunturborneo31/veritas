<?php

namespace App\Http\Controllers;

use App\Models\Greeting;
use App\Support\Greetings\CustomSlug;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GreetingSlugController extends Controller
{
    /**
     * Cek ketersediaan nama link saat pembeli mengetik di halaman checkout.
     */
    public function check(Request $request, Greeting $greeting, GreetingOwnership $ownership, CustomSlug $customSlug): JsonResponse
    {
        $ownership->authorize($request, $greeting);

        $slug = CustomSlug::normalize($request->string('slug')->toString());
        $problem = $customSlug->problem($slug, $greeting);

        return response()->json([
            'slug' => $slug,
            'available' => $problem === null,
            'message' => $problem ?? 'Link tersedia!',
            'url' => url("/u/{$slug}"),
            'suggestions' => $problem !== null && $slug !== '' ? $customSlug->suggestions($slug, $greeting) : [],
        ]);
    }
}
