<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Support\Greetings\QrCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GreetingQrController extends Controller
{
    /**
     * Unduh QR Code PNG resolusi tinggi (untuk dicetak di kado fisik). Hanya pemilik ucapan terbit.
     */
    public function __invoke(Request $request, Greeting $greeting, QrCodeGenerator $qr): Response
    {
        abort_unless($greeting->user_id === $request->user()->id && $greeting->status === GreetingStatus::Terbit, 404);

        $filename = 'qr-kartukita-'.Str::slug($greeting->recipient_name ?: 'ucapan').'.png';

        return response($qr->png(route('recipient.show', $greeting->slug)), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
