<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GreetingStatus;
use App\Http\Controllers\Controller;
use App\Models\Greeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GreetingController extends Controller
{
    /**
     * Kartu yang sudah terbit, untuk dicari lalu direvisi admin (termasuk yang terbit tanpa pesanan).
     */
    public function index(Request $request): Response
    {
        $search = trim($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '');

        $greetings = Greeting::query()
            ->with('user', 'template')
            ->where('status', GreetingStatus::Terbit)
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('slug', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhere('sender_name', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $query) => $query->where('email', 'like', "%{$search}%"))))
            ->latest('published_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Greeting $greeting): array => [
                'id' => $greeting->public_id,
                'slug' => $greeting->slug,
                'type' => $greeting->type->value,
                'recipient_name' => $greeting->recipient_name,
                'sender_name' => $greeting->sender_name,
                'template_name' => $greeting->template->name,
                'owner' => $greeting->user?->email,
                'published_at' => $greeting->published_at?->toIso8601String(),
                'public_url' => route('recipient.show', $greeting->slug),
            ]);

        return Inertia::render('Admin/Greetings/Index', [
            'greetings' => $greetings,
            'search' => $search,
        ]);
    }
}
