<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Enums\GreetingType;
use App\Models\Category;
use App\Models\Greeting;
use App\Models\Reply;
use App\Models\Template;
use App\Support\Greetings\GreetingPresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Angka di bawah ini belum meyakinkan sebagai bukti sosial, jadi bagian statistik disembunyikan.
     */
    public const SOCIAL_PROOF_MINIMUM = 50;

    /**
     * Beranda: pintu masuk menuju katalog, dengan demo kartu asli yang bisa langsung dicoba.
     */
    public function __invoke(GreetingPresenter $presenter): Response
    {
        // Beranda menonjolkan kartu ucapan; undangan punya katalognya sendiri.
        $featured = Template::query()
            ->published()
            ->where('type', GreetingType::Ucapan)
            ->with('category', 'series', 'components.component')
            ->orderBy('sort_order')
            ->get()
            ->unique(fn (Template $template): string => $template->catalogGroupKey())
            ->take(3)
            ->values();

        $showcase = $featured->first();

        return Inertia::render('Home', [
            'categories' => Category::query()
                ->withCount(['templates as invitation_templates_count' => fn ($query) => $query->published()->where('type', GreetingType::Undangan)])
                ->withCount(['templates as greeting_templates_count' => fn ($query) => $query->published()->where('type', GreetingType::Ucapan)])
                ->orderBy('sort_order')
                ->get()
                // Kategori yang hanya berisi undangan (mis. Pernikahan) diarahkan ke katalog undangan.
                ->map(fn (Category $category): array => [
                    ...$category->only(['name', 'slug', 'emoji']),
                    'jenis' => $category->greeting_templates_count === 0 && $category->invitation_templates_count > 0 ? GreetingType::Undangan->value : null,
                ]),
            'featured' => $featured->map(fn (Template $template): array => $template->toCardArray()),
            'showcase' => $showcase ? [
                'template' => $showcase->toCardArray(),
                'greeting' => $presenter->forTemplateSample($showcase),
            ] : null,
            'stats' => $this->socialProof(),
        ]);
    }

    /**
     * @return array{sent: int, replies: int}|null
     */
    private function socialProof(): ?array
    {
        $sent = Greeting::query()->whereIn('status', [GreetingStatus::Terbit, GreetingStatus::Arsip])->count();

        if ($sent < self::SOCIAL_PROOF_MINIMUM) {
            return null;
        }

        return ['sent' => $sent, 'replies' => Reply::query()->count()];
    }
}
