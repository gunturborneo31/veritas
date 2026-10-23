<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\User;
use App\Support\Greetings\DraftToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_anonymous_draft_owner_sees_checkout_with_account_form_instead_of_login(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create();

        $this->withCookie('kk_draft', 'perangkat-saya')
            ->get(route('checkout.show', $greeting))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Checkout/Show')->where('needsAccount', true));
    }

    public function test_visitor_without_the_draft_cookie_cannot_open_someone_elses_checkout(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create();

        $this->get(route('checkout.show', $greeting))->assertNotFound();
    }

    public function test_guest_checkout_creates_account_logs_in_and_continues_to_payment(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create(['content' => $this->completeContent()]);

        $response = $this->withCookie('kk_draft', 'perangkat-saya')->post(route('checkout.store', $greeting), [
            'confirm_data' => true,
            'name' => 'Sari Wulandari',
            'email' => 'sari@example.com',
            'phone' => '0812-3456-7890',
            'password' => 'rahasia-123',
        ]);

        $user = User::query()->where('email', 'sari@example.com')->sole();
        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('081234567890', $user->phone);
        $this->assertSame($user->id, $greeting->refresh()->user_id);
        $this->assertNull($greeting->draft_token_hash);
        $this->assertSame($user->id, $order->user_id);
    }

    public function test_registering_from_the_sign_up_page_returns_the_buyer_to_checkout(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create(['content' => $this->completeContent()]);
        $this->withCookie('kk_draft', 'perangkat-saya')->get(route('checkout.show', $greeting));

        $this->withCookie('kk_draft', 'perangkat-saya')
            ->post(route('register'), [
                'name' => 'Sari',
                'email' => 'sari@example.com',
                'password' => 'rahasia-123',
                'password_confirmation' => 'rahasia-123',
            ])
            ->assertRedirect(route('checkout.show', $greeting));

        $this->assertSame(User::query()->where('email', 'sari@example.com')->value('id'), $greeting->refresh()->user_id);
    }

    public function test_guest_checkout_with_a_registered_email_asks_to_log_in_first(): void
    {
        User::factory()->create(['email' => 'sari@example.com']);
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create(['content' => $this->completeContent()]);

        $this->withCookie('kk_draft', 'perangkat-saya')
            ->post(route('checkout.store', $greeting), [
                'confirm_data' => true, 'name' => 'Sari', 'email' => 'sari@example.com', 'phone' => '081234567890', 'password' => 'rahasia-123',
            ])
            ->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Masuk dulu, lalu lanjutkan pembayaran.']);

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_checkout_requires_account_data(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create(['content' => $this->completeContent()]);

        $this->withCookie('kk_draft', 'perangkat-saya')
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertSessionHasErrors([
                'name' => 'Tulis namamu.',
                'email' => 'Email wajib diisi untuk akunmu.',
                'phone' => 'Nomor WhatsApp wajib diisi untuk konfirmasi pembayaran.',
                'password' => 'Buat password untuk akunmu.',
            ]);
    }

    public function test_no_account_is_created_for_an_incomplete_card(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->anonymous(DraftToken::hash('perangkat-saya'))->create(['content' => ['components' => []]]);

        $this->withCookie('kk_draft', 'perangkat-saya')
            ->post(route('checkout.store', $greeting), [
                'confirm_data' => true, 'name' => 'Sari', 'email' => 'sari@example.com', 'phone' => '081234567890', 'password' => 'rahasia-123',
            ])
            ->assertSessionHasErrors('greeting');

        $this->assertDatabaseMissing('users', ['email' => 'sari@example.com']);
        $this->assertGuest();
    }

    public function test_order_amount_is_computed_on_server_and_ignores_browser_price(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate(price: 35000))->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true, 'amount' => 1000, 'price' => 1000])
            ->assertRedirect(route('orders.show', Order::sole()));

        $order = Order::sole();
        $this->assertSame(35000, $order->amount);
        $this->assertSame(OrderStatus::Menunggu, $order->status);
        $this->assertSame(GreetingStatus::MenungguBayar, $greeting->refresh()->status);
    }

    public function test_data_confirmation_checkbox_is_required(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), [])
            ->assertSessionHasErrors(['confirm_data' => 'Centang konfirmasi bahwa nama dan tanggal sudah kamu periksa.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_incomplete_greeting_cannot_be_ordered(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => ['components' => []]]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertSessionHasErrors('greeting');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_reordering_cancels_previous_pending_order(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);
        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $this->assertSame(1, $greeting->orders()->where('status', OrderStatus::Menunggu)->count());
        $this->assertSame(1, $greeting->orders()->where('status', OrderStatus::Batal)->count());
    }

    public function test_user_cannot_checkout_someone_elses_greeting(): void
    {
        $greeting = Greeting::factory()->for($this->createTemplate())->create(['content' => $this->completeContent()]);

        $this->actingAs(User::factory()->create())
            ->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertNotFound();
    }
}
