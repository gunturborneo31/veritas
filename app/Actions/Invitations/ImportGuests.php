<?php

namespace App\Actions\Invitations;

use App\Models\Greeting;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportGuests
{
    /**
     * Impor daftar tamu dari teks tempel: satu tamu per baris, kolom opsional
     * `nama<TAB>no. WA<TAB>grup<TAB>jumlah orang`. Tab adalah pemisah saat menyalin
     * dari Excel/Google Sheets; titik koma juga diterima. Koma sengaja bukan pemisah
     * karena sering muncul di nama bergelar (mis. "Andi, S.T.").
     *
     * Nama yang sudah ada di daftar (tanpa beda huruf besar/kecil) dilewati agar
     * tempel ulang tidak menggandakan tamu.
     *
     * @return array{created: int, skipped: int}
     */
    public function handle(Greeting $greeting, string $text): array
    {
        $rows = collect(preg_split('/\R/', $text))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        $maxRows = config('kartukita.invitation.max_import_rows');

        if ($rows->count() > $maxRows) {
            throw ValidationException::withMessages(['guests' => "Maksimal {$maxRows} tamu sekali impor. Bagi menjadi beberapa kali, ya."]);
        }

        $defaultQuota = $greeting->invitation->default_guest_quota;
        $maxQuota = config('kartukita.invitation.max_guest_quota');
        $knownNames = $greeting->guests()->pluck('name')->map(fn (string $name): string => Str::lower($name))->flip();

        $guests = [];
        $skipped = 0;

        foreach ($rows as $row) {
            [$name, $phone, $group, $quota] = array_pad(array_map('trim', preg_split('/\t|;/', $row)), 4, '');
            $name = Str::limit($name, 80, '');
            $key = Str::lower($name);

            if ($name === '' || $knownNames->has($key)) {
                $skipped++;

                continue;
            }

            $knownNames->put($key, true);
            $phone = preg_replace('/[^0-9+]/', '', $phone);

            $guests[] = [
                'name' => $name,
                'phone' => $phone !== '' && strlen($phone) <= 20 ? $phone : null,
                'group' => $group !== '' ? Str::limit($group, 40, '') : null,
                'quota' => is_numeric($quota) ? max(1, min($maxQuota, (int) $quota)) : $defaultQuota,
            ];
        }

        $maxGuests = config('kartukita.invitation.max_guests');

        if ($greeting->guests()->count() + count($guests) > $maxGuests) {
            throw ValidationException::withMessages(['guests' => "Daftar tamu maksimal {$maxGuests} orang."]);
        }

        DB::transaction(function () use ($greeting, $guests): void {
            foreach ($guests as $guest) {
                $greeting->guests()->create([...$guest, 'code' => Guest::generateCode($greeting)]);
            }
        });

        return ['created' => count($guests), 'skipped' => $skipped];
    }
}
