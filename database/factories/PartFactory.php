<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    protected $model = Part::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Engine',
                'Transmission',
                'Brakes',
                'Wheels',
                'Lights',
                'Sunroof',
                'Doors',
                'Hood',
                'Trunk',
                'Seats',
                'Steering Wheel',
                'Dashboard',
                'Air Conditioning',
                'Radio',
                'Battery'
            ]),
            'serialnumber' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'car_id' => Car::factory(),
        ];
    }
}
