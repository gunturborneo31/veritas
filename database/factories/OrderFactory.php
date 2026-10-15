<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Greeting;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'KK'.Str::upper(Str::random(10)),
            'greeting_id' => Greeting::factory(),
            'user_id' => fn (array $attributes) => Greeting::find($attributes['greeting_id'])->user_id,
            'amount' => 29000,
            'price_breakdown' => [['label' => 'Paket 1', 'amount' => 29000]],
            'status' => OrderStatus::Menunggu,
            'data_confirmed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
