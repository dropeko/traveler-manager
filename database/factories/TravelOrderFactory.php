<?php

namespace Database\Factories;

use App\Models\TravelOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    protected $model = TravelOrder::class;

    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('+5 days', '+30 days');
        $return = (clone $departure)->modify('+' . fake()->numberBetween(2, 10) . ' days');

        return [
            'order_code' => null,

            'requester_name' => fake()->name(),
            'destination' => fake()->randomElement(['Sao Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Curitiba']),
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => $return->format('Y-m-d'),
            'status' => TravelOrder::STATUS_REQUESTED,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => TravelOrder::STATUS_APPROVED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => TravelOrder::STATUS_CANCELLED,
        ]);
    }
}