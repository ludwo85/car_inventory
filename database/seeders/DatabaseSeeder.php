<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Part;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private const DEFAULT_CARS_COUNT = 10;
    private const MIN_PARTS_PER_CAR = 3;
    private const MAX_PARTS_PER_CAR = 8;

    public function run(): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Car> $cars */
        $cars = Car::factory(self::DEFAULT_CARS_COUNT)->create();

        foreach ($cars as $car) {
            Part::factory(rand(self::MIN_PARTS_PER_CAR, self::MAX_PARTS_PER_CAR))->create([
                'car_id' => $car->id,
            ]);
        }
    }
}
