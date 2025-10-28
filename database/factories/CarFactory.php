<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isRegistered = $this->faker->boolean(70);

        $carNames = [
            'Skoda Octavia',
            'Volkswagen Golf',
            'BMW X5',
            'Audi A4',
            'Mercedes-Benz C-Class',
            'Toyota Corolla',
            'Honda Civic',
            'Ford Focus',
            'Peugeot 308',
            'Renault Clio'
        ];
        
        $carName = $carNames[array_rand($carNames)];
        $year = (string) $this->faker->year();

        return [
            'name' => $carName . ' ' . $year,
            'registration_number' => $isRegistered ? $this->faker->regexify('[A-Z]{2}[0-9]{3}[A-Z]{2}') : null,
            'is_registered' => $isRegistered,
        ];
    }
}
