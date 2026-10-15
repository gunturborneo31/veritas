<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Component;
use App\Models\Series;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class AdminTemplateTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_admin_creates_a_design_in_a_new_series_with_its_own_theme(): void
    {
        $component = Component::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.templates.store'), $this->payload([
                'series_id' => '',
                'new_series_name' => 'Balon Terbang',
                'components' => [['component_id' => $component->id, 'key' => 'sampul', 'title' => '', 'is_required' => true]],
            ]))
            ->assertRedirect(route('admin.templates.index'));

        $template = Template::sole();
        $this->assertSame('Balon Terbang', $template->series->name);
        $this->assertSame('balon-terbang', $template->series->slug);
        $this->assertSame('Pastel Manis', $template->design_name);
        $this->assertSame('#127475', $template->theme['accent']);
        $this->assertSame('tulisan', $template->theme['font']);
        $this->assertSame('sampul', $template->components()->sole()->key);
    }

    public function test_series_is_required_when_no_new_series_name_is_given(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.templates.store'), $this->payload(['series_id' => '', 'new_series_name' => '']))
            ->assertSessionHasErrors(['new_series_name' => 'Pilih seri yang ada atau tulis nama seri baru.']);

        $this->assertDatabaseCount('templates', 0);
    }

    public function test_theme_color_must_be_a_hex_code(): void
    {
        $payload = $this->payload();
        $payload['theme']['accent'] = 'merah';

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.templates.store'), $payload)
            ->assertSessionHasErrors(['theme.accent' => 'Warna harus berupa kode hex, misalnya #a83223.']);
    }

    public function test_duplicating_a_design_creates_an_unpublished_copy_in_the_same_series(): void
    {
        $series = Series::factory()->create();
        $template = $this->createTemplate();
        $template->update(['series_id' => $series->id, 'design_name' => 'Pesta Klasik', 'theme' => ['accent' => '#c94a38']]);

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.templates.duplicate', $template));

        $copy = Template::where('slug', "{$template->slug}-salinan")->sole();
        $response->assertRedirect(route('admin.templates.edit', $copy));
        $this->assertFalse($copy->is_published);
        $this->assertSame($series->id, $copy->series_id);
        $this->assertSame('Pesta Klasik (Salinan)', $copy->design_name);
        $this->assertSame('#c94a38', $copy->theme['accent']);
        $this->assertSame(['sampul', 'gembok', 'surat'], $copy->components()->pluck('key')->all());
        $this->assertTrue($template->refresh()->is_published);
    }

    public function test_second_duplicate_gets_its_own_slug(): void
    {
        $template = $this->createTemplate();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.templates.duplicate', $template));
        $this->actingAs($admin)->post(route('admin.templates.duplicate', $template));

        $this->assertDatabaseHas('templates', ['slug' => "{$template->slug}-salinan-2"]);
    }

    public function test_non_admin_cannot_duplicate_templates(): void
    {
        $template = $this->createTemplate();

        $this->actingAs(User::factory()->create())->post(route('admin.templates.duplicate', $template))->assertForbidden();

        $this->assertDatabaseCount('templates', 1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'category_id' => Category::factory()->create()->id,
            'series_id' => Series::factory()->create()->id,
            'slug' => 'pastel-manis',
            'name' => 'Balon Terbang · Pastel Manis',
            'design_name' => 'Pastel Manis',
            'tier' => 'interaktif',
            'price' => 29000,
            'duration_minutes' => 3,
            'theme' => [
                'accent' => '#127475',
                'soft' => '#d5efee',
                'gradient' => ['#f3fbfa', '#dcf1ef', '#fde8d7'],
                'emoji' => '🎈',
                'motif' => 'confetti',
                'font' => 'tulisan',
            ],
            'is_published' => true,
            'sort_order' => 0,
            'components' => [['component_id' => Component::factory()->create()->id, 'key' => 'sampul', 'title' => '', 'is_required' => true]],
            ...$overrides,
        ];
    }
}
