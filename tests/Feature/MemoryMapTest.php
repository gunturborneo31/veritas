<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Greeting;
use App\Models\Template;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemoryMapTest extends TestCase
{
    use RefreshDatabase;

    private const DRAFT_TOKEN = 'token-jejak-kenangan';

    public function test_places_need_a_location_and_text_or_photo_before_they_count_as_complete(): void
    {
        $greeting = $this->draft();

        $this->saveDraft($greeting, [
            ['name' => 'Stasiun Bandung', 'lat' => -6.914744, 'lng' => 107.602243, 'caption' => 'Pertama ketemu'],
            ['name' => 'Jalan Braga', 'lat' => null, 'lng' => null, 'caption' => 'Belum ditandai di peta'],
            ['name' => 'Gambir', 'lat' => -6.176655, 'lng' => 106.830583, 'caption' => ''],
        ])->assertSessionHasNoErrors();

        $this->studio($greeting)->assertInertia(fn (Assert $page) => $page
            ->where('missing', fn ($missing) => collect($missing)->contains('field', 'places')));

        $this->saveDraft($greeting, [
            ['name' => 'Stasiun Bandung', 'lat' => -6.914744, 'lng' => 107.602243, 'caption' => 'Pertama ketemu'],
            ['name' => 'Jalan Braga', 'lat' => -6.917464, 'lng' => 107.609361, 'caption' => 'Jalan sore'],
        ])->assertSessionHasNoErrors();

        $this->studio($greeting)->assertInertia(fn (Assert $page) => $page
            ->where('missing', fn ($missing) => ! collect($missing)->contains('field', 'places')));
    }

    public function test_place_photo_alone_is_enough_and_is_resolved_to_a_media_url(): void
    {
        Storage::fake('local');
        $greeting = $this->draft();

        $media = $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->post(route('studio.media.store', $greeting), ['file' => UploadedFile::fake()->image('gambir.jpg'), 'kind' => 'image'])
            ->assertSuccessful()
            ->json('media');

        $this->saveDraft($greeting, [
            ['name' => 'Stasiun Bandung', 'lat' => -6.914744, 'lng' => 107.602243, 'caption' => 'Pertama ketemu'],
            ['name' => 'Gambir', 'lat' => -6.176655, 'lng' => 106.830583, 'caption' => '', 'media' => $media['id']],
        ])->assertSessionHasNoErrors();

        $this->studio($greeting)->assertInertia(fn (Assert $page) => $page
            ->where('greeting.values.jejak.places.1.media.id', $media['id'])
            ->where('missing', fn ($missing) => ! collect($missing)->contains('field', 'places')));
    }

    public function test_place_count_and_coordinates_are_limited(): void
    {
        $greeting = $this->draft();
        $place = ['name' => 'Titik', 'lat' => -6.9, 'lng' => 107.6, 'caption' => 'Cerita'];

        $this->saveDraft($greeting, [$place, $place, $place, $place])->assertSessionHasErrors('content.components.jejak.places');
        $this->saveDraft($greeting, [[...$place, 'lat' => 120]])->assertSessionHasErrors('content.components.jejak.places.0.lat');
    }

    public function test_place_search_is_proxied_with_app_user_agent_and_cached(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            ['name' => 'Stasiun Bandung', 'display_name' => 'Stasiun Bandung, Kebon Jeruk, Bandung, Jawa Barat', 'lat' => '-6.9147444', 'lon' => '107.6022431'],
        ])]);

        $this->getJson(route('locations.search', ['q' => 'Stasiun Bandung']))
            ->assertOk()
            ->assertJsonPath('results.0.name', 'Stasiun Bandung')
            ->assertJsonPath('results.0.lat', -6.914744)
            ->assertJsonPath('results.0.lng', 107.602243);

        $this->getJson(route('locations.search', ['q' => '  stasiun   BANDUNG ']))->assertOk()->assertJsonPath('results.0.name', 'Stasiun Bandung');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/search?')
            && str_contains($request->header('User-Agent')[0], config('kartukita.brand'))
            && ! str_contains($request->header('User-Agent')[0], 'example.com'));
    }

    public function test_place_search_reports_unavailable_service_without_caching_the_failure(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response('Access denied', 403)]);

        $this->getJson(route('locations.search', ['q' => 'Braga']))->assertStatus(503)->assertJsonPath('message', fn ($message) => str_contains($message, 'Google Maps'));
        $this->assertFalse(Cache::has('location-search:'.md5('braga')));
        $this->getJson(route('locations.search', ['q' => 'ab']))->assertUnprocessable();
    }

    public function test_empty_search_results_are_only_cached_briefly(): void
    {
        $this->freezeTime();
        Http::fake(['nominatim.openstreetmap.org/*' => Http::sequence()->push([])->push([
            ['name' => 'Kafe Baru', 'display_name' => 'Kafe Baru, Bandung', 'lat' => '-6.9', 'lon' => '107.6'],
        ])]);

        $this->getJson(route('locations.search', ['q' => 'Kafe Baru']))->assertOk()->assertJsonCount(0, 'results');

        $this->travel(61)->minutes();

        $this->getJson(route('locations.search', ['q' => 'Kafe Baru']))->assertOk()->assertJsonPath('results.0.name', 'Kafe Baru');
    }

    public function test_tapped_point_is_named_through_reverse_lookup(): void
    {
        Http::fake(['nominatim.openstreetmap.org/reverse*' => Http::response([
            'name' => 'Gedung Sate', 'display_name' => 'Gedung Sate, Jalan Diponegoro, Bandung', 'lat' => '-6.902477', 'lon' => '107.618781',
        ])]);

        $this->getJson(route('locations.reverse', ['lat' => -6.90248, 'lng' => 107.61879]))
            ->assertOk()
            ->assertJsonPath('results.0.name', 'Gedung Sate')
            ->assertJsonPath('results.0.lat', -6.9025);
    }

    public function test_google_maps_links_are_turned_into_coordinates(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/place/Stasiun+Bandung/@-6.9147444,107.6022431,17z/data=!3d-6.9147444!4d107.6022431']),
        ]);

        $this->getJson(route('locations.link', ['url' => 'https://www.google.com/maps/place/Jalan+Braga/@-6.917464,107.609361,17z']))
            ->assertOk()
            ->assertJsonPath('results.0.name', 'Jalan Braga')
            ->assertJsonPath('results.0.lat', -6.917464);

        $this->getJson(route('locations.link', ['url' => 'https://maps.app.goo.gl/AbCdEf123']))
            ->assertOk()
            ->assertJsonPath('results.0.name', 'Stasiun Bandung')
            ->assertJsonPath('results.0.lng', 107.602243);
    }

    public function test_link_lookup_refuses_non_google_hosts(): void
    {
        Http::fake();

        $this->getJson(route('locations.link', ['url' => 'http://169.254.169.254/latest/meta-data']))->assertUnprocessable();
        $this->getJson(route('locations.link', ['url' => 'https://evil.example/maps/@-6.9,107.6']))->assertUnprocessable();

        Http::assertNothingSent();
    }

    private function draft(): Greeting
    {
        $component = Component::factory()->create([
            'key' => 'jejak_lokasi',
            'schema' => [
                ['name' => 'title', 'type' => 'text', 'label' => 'Judul'],
                ['name' => 'places', 'type' => 'locations', 'label' => 'Titik kenangan', 'min' => 2, 'max' => 3, 'required' => true],
            ],
        ]);
        $template = Template::factory()->withComponents(['jejak' => $component])->create();

        return Greeting::factory()->for($template)->anonymous(DraftToken::hash(self::DRAFT_TOKEN))->create();
    }

    /**
     * @param  list<array<string, mixed>>  $places
     */
    private function saveDraft(Greeting $greeting, array $places): TestResponse
    {
        return $this->withCookie('kk_draft', self::DRAFT_TOKEN)
            ->put(route('studio.update', $greeting), ['content' => ['components' => ['jejak' => ['places' => $places]]]]);
    }

    private function studio(Greeting $greeting): TestResponse
    {
        return $this->withCookie('kk_draft', self::DRAFT_TOKEN)->get(route('studio.edit', $greeting));
    }
}
