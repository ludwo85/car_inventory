<?php

namespace Tests\Unit\Models;

use App\Models\Car;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartTest extends TestCase
{
    use RefreshDatabase;

    public function test_part_can_be_created(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create([
            'name' => 'Engine',
            'serialnumber' => 'ENG123456',
            'car_id' => $car->id,
        ]);

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'name' => 'Engine',
            'serialnumber' => 'ENG123456',
            'car_id' => $car->id,
        ]);
    }

    public function test_part_fillable_attributes(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        $part = new Part();
        $part->fill([
            'name' => 'Brakes',
            'serialnumber' => 'BRK789012',
            'car_id' => $car->id,
        ]);
        $part->save();

        $this->assertEquals('Brakes', $part->name);
        $this->assertEquals('BRK789012', $part->serialnumber);
        $this->assertEquals($car->id, $part->car_id);
    }

    public function test_part_belongs_to_car(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car->id]);

        $carRelation = $part->car()->first();
        $this->assertNotNull($carRelation);
        $this->assertInstanceOf(Car::class, $carRelation);
        /** @var Car $carRelation */
        $this->assertEquals($car->id, $carRelation->id);
        $this->assertEquals($car->name, $carRelation->name);
    }

    public function test_part_car_relationship(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $part->car());
        $firstCar = $part->car()->first();
        $this->assertNotNull($firstCar);
        $this->assertEquals($car->id, $firstCar->id);
    }

    public function test_part_requires_car_id(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Part::factory()->create(['car_id' => null]);
    }

    public function test_part_deleted_when_car_is_deleted(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part1 */
        $part1 = Part::factory()->create(['car_id' => $car->id]);
        /** @var Part $part2 */
        $part2 = Part::factory()->create(['car_id' => $car->id]);

        $car->delete();

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseMissing('parts', ['id' => $part1->id]);
        $this->assertDatabaseMissing('parts', ['id' => $part2->id]);
    }
}
