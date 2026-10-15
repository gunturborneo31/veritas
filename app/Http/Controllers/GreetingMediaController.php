<?php

namespace App\Http\Controllers;

use App\Actions\Media\StoreGreetingMedia;
use App\Enums\MediaKind;
use App\Exceptions\GreetingLockedException;
use App\Http\Requests\StoreGreetingMediaRequest;
use App\Models\Greeting;
use App\Models\Media;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint JSON untuk unggah media dari Studio (dipanggil lewat axios).
 */
class GreetingMediaController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    public function store(StoreGreetingMediaRequest $request, Greeting $greeting, StoreGreetingMedia $storeMedia): JsonResponse
    {
        $this->ownership->authorize($request, $greeting);

        $media = $storeMedia->handle(
            $greeting,
            $request->file('file'),
            MediaKind::from($request->validated('kind')),
        );

        return response()->json(['media' => $media->refresh()->toClientArray()], 201);
    }

    public function destroy(Request $request, Greeting $greeting, Media $media): JsonResponse
    {
        $this->ownership->authorize($request, $greeting);
        abort_unless($media->greeting_id === $greeting->id, 404);

        if (! $greeting->isEditable()) {
            throw GreetingLockedException::forGreeting($greeting);
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json(status: 204);
    }
}
