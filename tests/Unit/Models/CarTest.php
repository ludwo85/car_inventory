<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_can_be_created(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create([
            'name' => 'Test Car',
            'is_registered' => true,
            'registration_number' => 'AB123CD',
        ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'name' => 'Test Car',
            'is_registered' => true,
            'registration_number' => 'AB123CD',
        ]);
    }

    public function test_car_fillable_attributes(): void
    {
        $car = new Car();
        $car->fill([
            'name' => 'Test Car',
            'registration_number' => 'XY789ZW',
            'is_registered' => false,
        ]);
        $car->save();

        $this->assertEquals('Test Car', $car->name);
        $this->assertEquals('XY789ZW', $car->registration_number);
        $this->assertFalse($car->is_registered);
    }

    public function test_car_has_is_registered_cast_to_boolean(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create(['is_registered' => 1]);

        $this->assertTrue($car->is_registered);

        $car->is_registered = false;
        $car->save();

        $this->assertFalse($car->is_registered);
    }

    public function test_car_can_have_many_parts(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part1 */
        $part1 = Part::factory()->create(['car_id' => $car->id]);
        /** @var Part $part2 */
        $part2 = Part::factory()->create(['car_id' => $car->id]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Part> $parts */
        $parts = $car->parts()->get();
        $this->assertCount(2, $parts);
        $this->assertTrue($parts->contains($part1));
        $this->assertTrue($parts->contains($part2));
    }

    public function test_car_parts_relationship(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        Part::factory()->count(3)->create(['car_id' => $car->id]);

        $car->refresh();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $car->parts());
        $this->assertEquals(3, $car->parts()->count());
    }

    public function test_car_name_is_required_for_creation(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Car::factory()->create(['name' => null]);
    }

    public function test_car_registration_number_can_be_null(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create([
            'registration_number' => null,
            'is_registered' => false,
        ]);

        $this->assertNull($car->registration_number);
    }
}
