<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Exceptions\GreetingLockedException;
use App\Models\Greeting;
use App\Support\Greetings\ContentSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SaveGreetingDraft
{
    public function __construct(private ContentSchema $schema) {}

    /**
     * Simpan isi draf (autosave). Mengubah draf yang sedang menunggu bayar
     * membatalkan pesanan lama, karena konfirmasi data harus diulang.
     *
     * @param  array{recipient_name?: ?string, sender_name?: ?string, occasion?: ?string, content?: ?array<string, mixed>}  $data
     */
    public function handle(Greeting $greeting, array $data): Greeting
    {
        if (! $greeting->isEditable()) {
            throw GreetingLockedException::forGreeting($greeting);
        }

        $manifest = $greeting->template->manifest();
        ['content' => $content, 'pins' => $pins] = $this->schema->sanitize($manifest, $data['content'] ?? []);

        return DB::transaction(function () use ($greeting, $data, $content, $pins, $manifest): Greeting {
            if ($greeting->status === GreetingStatus::MenungguBayar) {
                $greeting->orders()
                    ->where('status', OrderStatus::Menunggu)
                    ->update(['status' => OrderStatus::Batal, 'cancelled_at' => now()]);
            }

            $greeting->fill([
                ...array_intersect_key($data, array_flip(['recipient_name', 'sender_name', 'occasion', 'opens_at'])),
                'content' => ['components' => array_replace($greeting->content['components'] ?? [], $content['components'])],
                'status' => GreetingStatus::Draf,
                'last_edited_at' => now(),
            ])->save();

            $this->syncGate($greeting, $manifest, $pins);

            return $greeting;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $manifest
     * @param  array<string, ?string>  $pins
     */
    private function syncGate(Greeting $greeting, array $manifest, array $pins): void
    {
        $gateSlot = collect($manifest)->firstWhere('component', 'gembok_pin');

        if ($gateSlot === null) {
            return;
        }

        $values = $greeting->content['components'][$gateSlot['key']] ?? [];

        if (! $this->schema->isSlotEnabled($gateSlot, $greeting->content['components'] ?? [])) {
            $greeting->gate()->delete();

            return;
        }

        $pin = $pins[$gateSlot['key']] ?? null;
        $gate = $greeting->gate()->firstOrNew();

        if (filled($pin)) {
            $gate->answer_hash = Hash::make($pin);
            $gate->answer_length = strlen($pin);
        }

        if (! $gate->exists && blank($pin)) {
            return;
        }

        $gate->fill([
            'question' => $values['question'] ?? null,
            'hint' => $values['hint'] ?? null,
        ])->save();
    }
}
