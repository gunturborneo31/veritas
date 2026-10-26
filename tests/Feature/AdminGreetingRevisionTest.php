<?php

namespace Tests\Feature;

use App\Models\Greeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

/**
 * Admin boleh merevisi isi kartu terbit milik siapa pun (kartu ucapan biasanya terkunci permanen);
 * revisi menjadi snapshot baru dan link kartu tetap sama. Selain admin, tidak ada yang bisa.
 */
class AdminGreetingRevisionTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_admin_opens_someone_elses_published_card_in_admin_revision_mode(): void
    {
        $greeting = $this->publishedGreeting();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('studio.edit', $greeting))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Studio/Edit')
                ->where('revision.is_admin_edit', true)
                ->where('revision.owner.email', $greeting->user->email)
                ->where('revision.back_url', route('admin.greetings.index')));
    }

    public function test_admin_revision_creates_a_new_snapshot_and_keeps_the_link(): void
    {
        $greeting = $this->publishedGreeting();
        $slug = $greeting->slug;

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('studio.revise', $greeting), [
                'recipient_name' => 'Amanda Putri',
                'content' => ['components' => ['surat' => ['body' => 'Selamat ulang tahun ke-23!']]],
            ])
            ->assertSessionHasNoErrors();

        $greeting->refresh();
        $this->assertSame($slug, $greeting->slug);
        $this->assertSame(2, $greeting->snapshot->revision);
        $this->assertSame('Amanda Putri', $greeting->snapshot->recipient_name);
        $this->assertSame('Selamat ulang tahun ke-23!', $greeting->snapshot->content['components']['surat']['body']);
        $this->assertSame('Semoga panjang umur.', $greeting->snapshots()->where('revision', 1)->value('content')['components']['surat']['body']);

        $this->get(route('recipient.show', $slug))
            ->assertInertia(fn (Assert $page) => $page->where('recipient_name', 'Amanda Putri'));
    }

    public function test_admin_can_add_a_photo_while_revising(): void
    {
        Storage::fake('local');
        $greeting = $this->publishedGreeting();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('studio.media.store', $greeting), ['file' => UploadedFile::fake()->image('kue.jpg'), 'kind' => 'image'])
            ->assertCreated();

        $this->assertSame(1, $greeting->media()->count());
    }

    public function test_other_users_cannot_open_or_revise_someone_elses_card(): void
    {
        $greeting = $this->publishedGreeting();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('studio.edit', $greeting))->assertNotFound();
        $this->actingAs($stranger)->put(route('studio.revise', $greeting), ['recipient_name' => 'Iseng'])->assertNotFound();
        $this->actingAs($stranger)
            ->post(route('studio.media.store', $greeting), ['file' => UploadedFile::fake()->image('x.jpg'), 'kind' => 'image'])
            ->assertNotFound();

        $this->assertSame(1, $greeting->snapshots()->count());
    }

    public function test_admin_cannot_open_someone_elses_unpublished_draft(): void
    {
        $draft = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('studio.edit', $draft))
            ->assertNotFound();
    }

    public function test_admin_card_list_shows_published_cards_and_searches_by_link(): void
    {
        $template = $this->createTemplate();
        $amanda = Greeting::factory()->for($template)->published()->create(['content' => $this->completeContent(), 'slug' => 'amanda']);
        Greeting::factory()->for($template)->published()->create(['content' => $this->completeContent(), 'slug' => 'budi']);
        Greeting::factory()->for($template)->create(['recipient_name' => 'Amanda Draf']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.greetings.index', ['q' => 'amanda']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Greetings/Index')
                ->has('greetings.data', 1)
                ->where('greetings.data.0.id', $amanda->public_id));
    }

    public function test_admin_card_list_is_only_for_admins(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.greetings.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedGreeting(array $attributes = []): Greeting
    {
        return Greeting::factory()->for($this->createTemplate())->published()->create(['content' => $this->completeContent(), ...$attributes]);
    }
}
