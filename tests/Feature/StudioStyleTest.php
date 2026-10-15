<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class StudioStyleTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    private const DRAFT_TOKEN = 'token-anonim-pengunjung-pertama';

    private const STYLE = [
        'accent' => '#127475',
        'soft' => '#d5efee',
        'gradient' => ['#f3fbfa', '#dcf1ef', '#fde8d7'],
        'motif' => 'petals',
        'font' => 'tulisan',
    ];

    public function test_owner_saves_custom_style_without_touching_the_content(): void
    {
        $greeting = $this->anonymousDraft(['components' => ['surat' => ['body' => 'Halo']]]);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.style.update', $greeting), ['style' => self::STYLE])
            ->assertSessionHasNoErrors();

        $greeting->refresh();
        $this->assertEquals(self::STYLE, $greeting->content['style']);
        $this->assertSame(['surat' => ['body' => 'Halo']], $greeting->content['components']);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)->get(route('studio.style', $greeting))->assertInertia(fn (Assert $page) => $page
            ->component('Studio/Style')
            ->where('greeting.theme.accent', '#127475')
            ->where('greeting.theme.font', 'tulisan')
            ->where('templateTheme.accent', $greeting->template->resolvedTheme()['accent']));
    }

    public function test_autosaving_content_keeps_the_chosen_style(): void
    {
        $greeting = $this->anonymousDraft(['components' => [], 'style' => self::STYLE]);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.update', $greeting), ['content' => ['components' => ['surat' => ['body' => 'Baru']]]])
            ->assertSessionHasNoErrors();

        $this->assertSame(self::STYLE, $greeting->refresh()->content['style']);
    }

    public function test_resetting_style_returns_to_the_template_colors(): void
    {
        $greeting = $this->anonymousDraft(['components' => [], 'style' => self::STYLE]);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.style.update', $greeting), ['style' => null])
            ->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('style', $greeting->refresh()->content);
    }

    public function test_style_rejects_colors_that_are_not_hex_codes(): void
    {
        $greeting = $this->anonymousDraft(['components' => []]);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.style.update', $greeting), ['style' => [...self::STYLE, 'accent' => 'red; background:url(x)']])
            ->assertSessionHasErrors(['style.accent' => 'Warna harus berupa kode hex, misalnya #a83223.']);

        $this->assertArrayNotHasKey('style', $greeting->refresh()->content);
    }

    public function test_style_rejects_unknown_motif(): void
    {
        $greeting = $this->anonymousDraft(['components' => []]);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.style.update', $greeting), ['style' => [...self::STYLE, 'motif' => 'naga']])
            ->assertSessionHasErrors('style.motif');
    }

    public function test_other_visitor_cannot_restyle_someone_elses_draft(): void
    {
        $greeting = $this->anonymousDraft(['components' => []]);

        $this->withCookie('kk_draft', 'token-pengunjung-lain')
            ->put(route('studio.style.update', $greeting), ['style' => self::STYLE])
            ->assertNotFound();

        $this->assertArrayNotHasKey('style', $greeting->refresh()->content);
    }

    public function test_locked_greeting_style_cannot_be_changed(): void
    {
        $greeting = $this->anonymousDraft(['components' => []]);
        $greeting->update(['status' => GreetingStatus::Terkunci]);

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.style.update', $greeting), ['style' => self::STYLE])
            ->assertSessionHas('error');

        $this->assertArrayNotHasKey('style', $greeting->refresh()->content);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function anonymousDraft(array $content): Greeting
    {
        return Greeting::factory()
            ->for($this->createTemplate())
            ->anonymous(DraftToken::hash(self::DRAFT_TOKEN))
            ->status(GreetingStatus::Draf)
            ->create(['content' => $content]);
    }
}
