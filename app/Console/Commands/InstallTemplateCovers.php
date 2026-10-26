<?php

namespace App\Console\Commands;

use App\Models\Template;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kartukita:install-covers')]
#[Description('Pasang sampul bawaan (public/images/templates/<slug>.webp) ke template yang belum punya sampul.')]
class InstallTemplateCovers extends Command
{
    /**
     * Hanya template tanpa sampul yang diisi, jadi sampul unggahan admin tidak pernah tertimpa.
     * Aman dijalankan berulang (mis. di setiap deploy).
     */
    public function handle(): int
    {
        $installed = 0;

        Template::query()
            ->whereNull('cover_path')
            ->lazyById()
            ->each(function (Template $template) use (&$installed): void {
                $cover = "/images/templates/{$template->slug}.webp";

                if (is_file(public_path($cover))) {
                    $template->update(['cover_path' => $cover]);
                    $installed++;
                }
            });

        $this->info("{$installed} sampul dipasang.");

        return self::SUCCESS;
    }
}
