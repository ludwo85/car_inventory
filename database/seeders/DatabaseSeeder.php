<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Part;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Car> $cars */
        $cars = Car::factory(10)->create();

        foreach ($cars as $car) {
            Part::factory(rand(3, 8))->create([
                'car_id' => $car->id,
            ]);
        }
    }
}
