<?php

namespace Tests\Concerns;

use App\Enums\GreetingType;
use App\Models\Component;
use App\Models\Template;

trait CreatesGreetingTemplates
{
    /**
     * Template minimal: sampul (judul wajib), gembok PIN opsional, dan surat (isi wajib).
     */
    protected function createTemplate(int $price = 29000): Template
    {
        return Template::factory()
            ->withComponents([
                'sampul' => Component::factory()->create([
                    'key' => 'sampul',
                    'schema' => [['name' => 'title', 'type' => 'text', 'label' => 'Judul', 'required' => true]],
                ]),
                'gembok' => Component::factory()->pinGate()->create(),
                'surat' => Component::factory()->create([
                    'key' => 'surat',
                    'schema' => [['name' => 'body', 'type' => 'textarea', 'label' => 'Isi surat', 'required' => true]],
                ]),
            ])
            ->create(['price' => $price]);
    }

    /**
     * Template yang sama, tetapi menghasilkan undangan.
     */
    protected function createInvitationTemplate(): Template
    {
        return tap($this->createTemplate())->update(['type' => GreetingType::Undangan]);
    }

    /**
     * Isi ucapan yang memenuhi semua field wajib template di atas.
     *
     * @return array{components: array<string, array<string, mixed>>}
     */
    protected function completeContent(): array
    {
        return [
            'components' => [
                'sampul' => ['title' => 'Selamat Ulang Tahun!'],
                'gembok' => ['enabled' => false],
                'surat' => ['body' => 'Semoga panjang umur.'],
            ],
        ];
    }
}
