<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => 'transfer',
            'amount' => 29000,
            'proof_disk' => 'local',
            'proof_path' => 'payments/test/proof.jpg',
            'sender_account_name' => fake()->name(),
            'status' => PaymentStatus::Menunggu,
        ];
    }
}
