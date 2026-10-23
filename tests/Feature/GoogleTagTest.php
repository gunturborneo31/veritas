<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tag Google Analytics: satu kali di setiap halaman, tepat setelah <head>, dan bisa dimatikan.
 */
class GoogleTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_tag_is_rendered_once_right_after_head_when_configured(): void
    {
        config(['services.google_analytics.measurement_id' => 'G-JVSC2D80V8']);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'googletagmanager.com/gtag/js?id=G-JVSC2D80V8'));
        $this->assertMatchesRegularExpression('/<head>\s*<!-- Google tag \(gtag\.js\) -->/', $html);
        $this->assertStringContainsString("gtag('config', \"G-JVSC2D80V8\", { send_page_view: false });", $html);
    }

    public function test_google_tag_is_omitted_when_not_configured(): void
    {
        config(['services.google_analytics.measurement_id' => null]);

        $this->get(route('home'))->assertOk()->assertDontSee('googletagmanager.com', escape: false);
    }
}
