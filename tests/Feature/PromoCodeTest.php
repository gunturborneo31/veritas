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

    public function test_full_discount_code_publishes_the_card_for_free_without_qris(): void
    {
        $greeting = $this->completeDraft(price: 30000);
        $promo = PromoCode::factory()->percent(100)->create(['code' => 'GRATIS', 'max_uses' => 1]);

        $this->actingAs($greeting->user)->post(route('checkout.promo.apply', $greeting), ['promo_code' => 'GRATIS']);
        $this->actingAs($greeting->user)->get(route('checkout.show', $greeting))
            ->assertInertia(fn (Assert $page) => $page->where('quote.total', 0)->where('quote.discount', 30000));

        $this->actingAs($greeting->user)->post(route('checkout.store', $greeting), ['confirm_data' => true])
            ->assertRedirect(route('result.show', $greeting));

        $order = Order::sole();
        $this->assertSame(0, $order->amount);
        $this->assertSame(OrderStatus::Lunas, $order->status);
        $this->assertSame(0, $order->payments()->count());
        $this->assertSame(GreetingStatus::Terbit, $greeting->fresh()->status);
        $this->assertSame(1, $promo->fresh()->uses_count);
    }

    public function test_admin_can_edit_a_promo_code(): void
    {
        $promo = PromoCode::factory()->create(['code' => 'LAMA', 'discount_value' => 5000]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.promos.update', $promo), [
                'code' => 'baru',
                'label' => 'Promo baru',
                'discount_type' => PromoCode::TYPE_PERCENT,
                'discount_value' => 50,
                'max_uses' => 1,
                'first_order_only' => false,
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $promo->refresh();
        $this->assertSame('BARU', $promo->code);
        $this->assertSame(PromoCode::TYPE_PERCENT, $promo->discount_type);
        $this->assertSame(50, $promo->discount_value);
        $this->assertSame(1, $promo->max_uses);
    }

    public function test_admin_can_delete_a_used_promo_and_past_orders_keep_their_discount(): void
    {
        $promo = PromoCode::factory()->create(['code' => 'HAPUS']);
        $order = Order::factory()->create(['promo_code_id' => $promo->id, 'discount' => 5000, 'amount' => 24000]);

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.promos.destroy', $promo))->assertRedirect();

        $this->assertModelMissing($promo);
        $order->refresh();
        $this->assertNull($order->promo_code_id);
        $this->assertSame(5000, $order->discount);
    }

    public function test_promo_codes_cannot_be_deleted_by_non_admins_and_referral_codes_cannot_be_deleted_at_all(): void
    {
        $promo = PromoCode::factory()->create();
        $referral = PromoCode::factory()->referralOf(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())->delete(route('admin.promos.destroy', $promo))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.promos.destroy', $referral))->assertNotFound();

        $this->assertModelExists($promo);
        $this->assertModelExists($referral);
    }

    private function completeDraft(int $price = 29000): Greeting
    {
        return Greeting::factory()->for($this->createTemplate($price))->create(['content' => $this->completeContent()]);
    }
}
