<?php

namespace App\Actions\Invitations;

use App\Actions\Greetings\PublishGreeting;
use App\Models\Greeting;
use App\Models\GreetingSnapshot;
use App\Support\Greetings\ContentSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviseInvitation
{
    public function __construct(
        private ContentSchema $schema,
        private PublishGreeting $publishGreeting,
    ) {}

    /**
     * Revisi isi undangan yang sudah terbit tanpa bayar ulang: isi baru dibekukan menjadi
     * snapshot revisi berikutnya, slug tetap, sehingga link yang sudah disebar tidak berubah.
     *
     * @param  array{recipient_name?: ?string, sender_name?: ?string, occasion?: ?string, content?: ?array<string, mixed>}  $data
     */
    public function handle(Greeting $greeting, array $data): GreetingSnapshot
    {
        if (! $greeting->isRevisable()) {
            throw ValidationException::withMessages(['greeting' => 'Masa revisi undangan ini sudah berakhir.']);
        }

        $manifest = $greeting->template->manifest();
        ['content' => $content] = $this->schema->sanitize($manifest, $data['content'] ?? []);

        $greeting->fill([
            ...array_intersect_key($data, array_flip(['recipient_name', 'sender_name', 'occasion'])),
            'content' => [
                ...($greeting->content ?? []),
                'components' => array_replace($greeting->content['components'] ?? [], $content['components']),
            ],
        ]);

        $missing = $this->schema->missingFields($manifest, $greeting);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'greeting' => 'Lengkapi dulu: '.collect($missing)->pluck('label')->join(', ').'.',
            ]);
        }

        if (! $greeting->isDirty()) {
            return $greeting->snapshot;
        }

        return DB::transaction(function () use ($greeting): GreetingSnapshot {
            $greeting->forceFill(['last_edited_at' => now()])->save();

            return $this->publishGreeting->freeze($greeting);
        });
    }
}
