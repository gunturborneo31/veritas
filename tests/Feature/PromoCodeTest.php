<?php

namespace Tests\Feature;

use App\Enums\GreetingStatus;
use App\Enums\OrderStatus;
use App\Enums\PromoKind;
use App\Models\Greeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PromoCode;
use App\Models\User;
use App\Notifications\ReferralRewarded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGreetingTemplates;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use CreatesGreetingTemplates, RefreshDatabase;

    public function test_applied_promo_discounts_order_computed_on_server(): void
    {
        $greeting = $this->completeDraft(price: 30000);
        PromoCode::factory()->percent(20)->create(['code' => 'HEMAT20']);

        $this->actingAs($greeting->user)->post(route('checkout.promo.apply', $greeting), ['promo_code' => 'hemat20'])->assertSessionHasNoErrors();
        $this->actingAs($greeting->user)->get(route('checkout.show', $greeting))
            ->assertInertia(fn (Assert $page) => $page->where('quote.total', 24000)->where('quote.discount', 6000));

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true, 'amount' => 1]);

        $order = Order::sole();
        $this->assertSame(24000, $order->amount);
        $this->assertSame(6000, $order->discount);
    }

    public function test_discount_never_goes_below_minimum_payable(): void
    {
        $greeting = $this->completeDraft(price: 3000);
        PromoCode::factory()->create(['code' => 'BESAR', 'discount_value' => 50000]);

        $this->actingAs($greeting->user)->post(route('checkout.promo.apply', $greeting), ['promo_code' => 'BESAR']);
        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true]);

        $this->assertSame(1000, Order::sole()->amount);
    }

    public function test_invalid_codes_are_rejected_with_clear_messages(): void
    {
        $greeting = $this->completeDraft();
        PromoCode::factory()->create(['code' => 'LEWAT', 'ends_at' => now()->subDay()]);
        PromoCode::factory()->create(['code' => 'HABIS', 'max_uses' => 1, 'uses_count' => 1]);
        PromoCode::factory()->referralOf($greeting->user)->create(['code' => 'MILIKKU']);

        $apply = fn (string $code) => $this->actingAs($greeting->user)->post(route('checkout.promo.apply', $greeting), ['promo_code' => $code]);

        $apply('TIDAKADA')->assertSessionHasErrors(['promo_code' => 'Kode tidak ditemukan atau sudah tidak berlaku.']);
        $apply('LEWAT')->assertSessionHasErrors(['promo_code' => 'Kode ini sudah kedaluwarsa.']);
        $apply('HABIS')->assertSessionHasErrors(['promo_code' => 'Kuota kode ini sudah habis.']);
        $apply('MILIKKU')->assertSessionHasErrors(['promo_code' => 'Kode referral milikmu sendiri tidak bisa dipakai. Bagikan ke teman, ya!']);
    }

    public function test_referral_code_is_first_order_only(): void
    {
        $greeting = $this->completeDraft();
        Order::factory()->for(Greeting::factory()->for($greeting->template)->for($greeting->user))->status(OrderStatus::Lunas)->create(['user_id' => $greeting->user_id]);
        PromoCode::factory()->referralOf(User::factory()->create())->create(['code' => 'TEMANKU']);

        $this->actingAs($greeting->user)
            ->post(route('checkout.promo.apply', $greeting), ['promo_code' => 'TEMANKU'])
            ->assertSessionHasErrors(['promo_code' => 'Kode ini hanya untuk pesanan pertama.']);
    }

    public function test_paid_referral_order_counts_use_and_rewards_the_code_owner(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $referral = PromoCode::factory()->referralOf($owner)->create(['code' => 'TEMANKU']);
        $greeting = $this->completeDraft();
        $greeting->update(['status' => GreetingStatus::Terkunci]);
        $order = Order::factory()->for($greeting)->status(OrderStatus::MenungguKonfirmasi)->create(['promo_code_id' => $referral->id]);
        Payment::factory()->for($order)->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.orders.confirm', $order));

        $this->assertSame(1, $referral->refresh()->uses_count);
        $reward = PromoCode::query()->where('kind', PromoKind::Reward)->sole();
        $this->assertSame($owner->id, $reward->restricted_to_user_id);
        Notification::assertSentTo($owner, ReferralRewarded::class);
    }

    public function test_reward_voucher_can_only_be_used_by_its_owner(): void
    {
        $greeting = $this->completeDraft();
        PromoCode::factory()->create(['code' => 'HADIAHX', 'kind' => PromoKind::Reward, 'restricted_to_user_id' => User::factory()->create()->id]);

        $this->actingAs($greeting->user)
            ->post(route('checkout.promo.apply', $greeting), ['promo_code' => 'HADIAHX'])
            ->assertSessionHasErrors(['promo_code' => 'Kode ini khusus untuk akun lain.']);
    }

    public function test_dashboard_creates_a_personal_referral_code_once(): void
    {
        $user = User::factory()->create(['name' => 'Nadia Putri']);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('referral.code', fn (string $code) => str_starts_with($code, 'NADIA')));
        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame(1, PromoCode::query()->where('owner_user_id', $user->id)->count());
    }

    private function completeDraft(int $price = 29000): Greeting
    {
        return Greeting::factory()->for($this->createTemplate($price))->create(['content' => $this->completeContent()]);
    }
}
