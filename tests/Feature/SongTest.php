<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Greeting;
use App\Models\Template;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SongTest extends TestCase
{
    use RefreshDatabase;

    private const DRAFT_TOKEN = 'token-lagu-kita';

    private const SPOTIFY_ID = '4cOdK2wGLETKBW3PvgPWqT';

    public function test_spotify_link_is_resolved_through_oembed_without_credentials(): void
    {
        Http::fake(['open.spotify.com/oembed*' => Http::response(['title' => 'Tak Segampang Itu', 'thumbnail_url' => 'https://image-cdn-ak.spotifycdn.com/image/ab67616d'])]);

        $this->getJson(route('songs.link', ['url' => 'https://open.spotify.com/intl-id/track/'.self::SPOTIFY_ID.'?si=abc123']))
            ->assertOk()
            ->assertJsonPath('song.provider', 'spotify')
            ->assertJsonPath('song.id', self::SPOTIFY_ID)
            ->assertJsonPath('song.title', 'Tak Segampang Itu');
    }

    public function test_spotify_short_link_is_followed_only_to_spotify(): void
    {
        Http::fake([
            'spotify.link/*' => Http::response('', 307, ['Location' => 'https://open.spotify.com/track/'.self::SPOTIFY_ID]),
            'open.spotify.com/oembed*' => Http::response(['title' => 'Sial']),
        ]);

        $this->getJson(route('songs.link', ['url' => 'https://spotify.link/xYz123']))->assertOk()->assertJsonPath('song.id', self::SPOTIFY_ID);
    }

    public function test_youtube_links_in_any_common_form_are_recognised(): void
    {
        Http::fake(['www.youtube.com/oembed*' => Http::response(['title' => 'Sheila On 7 - Dan', 'author_name' => 'Sheila On 7'])]);

        foreach ([
            'https://youtu.be/dQw4w9WgXcQ?si=share',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=RD',
            'https://youtube.com/shorts/dQw4w9WgXcQ',
            'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
        ] as $url) {
            $this->getJson(route('songs.link', ['url' => $url]))
                ->assertOk()
                ->assertJsonPath('song.provider', 'youtube')
                ->assertJsonPath('song.id', 'dQw4w9WgXcQ')
                ->assertJsonPath('song.artist', 'Sheila On 7');
        }
    }

    public function test_youtube_video_that_blocks_embedding_is_explained_to_the_sender(): void
    {
        Http::fake(['www.youtube.com/oembed*' => Http::response('Unauthorized', 401)]);

        $this->getJson(route('songs.link', ['url' => 'https://youtu.be/dQw4w9WgXcQ']))
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'tidak mengizinkannya diputar di situs lain'));
    }

    public function test_links_from_other_sites_are_rejected_without_any_request(): void
    {
        Http::fake();

        $this->getJson(route('songs.link', ['url' => 'https://soundcloud.com/someone/song']))->assertUnprocessable();
        $this->getJson(route('songs.link', ['url' => 'http://169.254.169.254/latest']))->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_spotify_search_is_off_until_credentials_are_configured(): void
    {
        config(['services.spotify.client_id' => null, 'services.spotify.client_secret' => null]);
        Http::fake();

        $this->getJson(route('songs.search', ['q' => 'sheila on 7']))->assertStatus(503);
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('song_search_enabled', false));

        Http::assertNothingSent();
    }

    public function test_spotify_search_reuses_the_access_token(): void
    {
        config(['services.spotify.client_id' => 'id', 'services.spotify.client_secret' => 'secret']);
        Http::fake([
            'accounts.spotify.com/api/token' => Http::response(['access_token' => 'token-123', 'expires_in' => 3600]),
            'api.spotify.com/v1/search*' => Http::response(['tracks' => ['items' => [[
                'id' => self::SPOTIFY_ID,
                'name' => 'Dan',
                'artists' => [['name' => 'Sheila On 7']],
                'album' => ['images' => [['url' => 'https://i.scdn.co/image/large', 'width' => 640], ['url' => 'https://i.scdn.co/image/medium', 'width' => 300]]],
            ]]]]),
        ]);

        $this->getJson(route('songs.search', ['q' => 'dan sheila']))
            ->assertOk()
            ->assertJsonPath('results.0.title', 'Dan')
            ->assertJsonPath('results.0.artist', 'Sheila On 7')
            ->assertJsonPath('results.0.image', 'https://i.scdn.co/image/medium');
        $this->getJson(route('songs.search', ['q' => 'sephia']))->assertOk();

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://accounts.spotify.com/api/token');
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'v1/search') && $request->hasHeader('Authorization', 'Bearer token-123'));
    }

    public function test_chosen_song_is_saved_and_completes_the_section(): void
    {
        $greeting = $this->draft();

        $this->saveSong($greeting, ['provider' => 'spotify', 'id' => self::SPOTIFY_ID, 'title' => 'Dan', 'artist' => 'Sheila On 7', 'image' => 'https://i.scdn.co/image/medium'])
            ->assertSessionHasNoErrors();

        $this->withCookie('kk_draft', self::DRAFT_TOKEN)->get(route('studio.edit', $greeting))->assertInertia(fn (Assert $page) => $page
            ->where('greeting.values.lagu.song.id', self::SPOTIFY_ID)
            ->where('missing', fn ($missing) => ! collect($missing)->contains('field', 'song')));
    }

    public function test_song_with_unknown_provider_bad_id_or_foreign_image_is_rejected(): void
    {
        $greeting = $this->draft();

        $this->saveSong($greeting, ['provider' => 'soundcloud', 'id' => self::SPOTIFY_ID, 'title' => 'x'])->assertSessionHasErrors('content.components.lagu.song.provider');
        $this->saveSong($greeting, ['provider' => 'youtube', 'id' => '"><script>', 'title' => 'x'])->assertSessionHasErrors('content.components.lagu.song.id');
        $this->saveSong($greeting, ['provider' => 'youtube', 'id' => 'dQw4w9WgXcQ', 'title' => 'x', 'image' => 'https://tracker.example/pixel.gif'])
            ->assertSessionHasErrors('content.components.lagu.song.image');
    }

    private function draft(): Greeting
    {
        $component = Component::factory()->create([
            'key' => 'lagu_kita',
            'schema' => [
                ['name' => 'song', 'type' => 'song', 'label' => 'Lagu', 'required' => true],
                ['name' => 'note', 'type' => 'textarea', 'label' => 'Catatan'],
            ],
        ]);
        $template = Template::factory()->withComponents(['lagu' => $component])->create();

        return Greeting::factory()->for($template)->anonymous(DraftToken::hash(self::DRAFT_TOKEN))->create();
    }

    /**
     * @param  array<string, mixed>  $song
     */
    private function saveSong(Greeting $greeting, array $song): TestResponse
    {
        return $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.update', $greeting), ['content' => ['components' => ['lagu' => ['song' => $song]]]]);
    }
}
