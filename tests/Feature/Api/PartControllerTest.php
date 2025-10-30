<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\PartController;
use App\Models\Car;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_parts(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        Part::factory()->count(3)->create(['car_id' => $car->id]);

        $response = $this->getJson('/api/parts');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'serialnumber', 'car'],
                ],
                'current_page',
                'last_page',
            ]);

        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(3, $data);
    }

    public function test_can_filter_parts_by_search(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        Part::factory()->create([
            'name' => 'Engine',
            'car_id' => $car->id,
        ]);
        Part::factory()->create([
            'name' => 'Brakes',
            'car_id' => $car->id,
        ]);

        $response = $this->getJson('/api/parts?search=Engine');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(1, $data);
        $firstPart = $data[0];
        assert(is_array($firstPart));
        $this->assertEquals('Engine', $firstPart['name'] ?? null);
    }

    public function test_can_filter_parts_by_serial_number(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        Part::factory()->create([
            'serialnumber' => 'ABC123',
            'car_id' => $car->id,
        ]);
        Part::factory()->create([
            'serialnumber' => 'XYZ789',
            'car_id' => $car->id,
        ]);

        $response = $this->getJson('/api/parts?search=ABC');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(1, $data);
        $firstPart = $data[0];
        assert(is_array($firstPart));
        $this->assertEquals('ABC123', $firstPart['serialnumber'] ?? null);
    }

    public function test_can_filter_parts_by_car(): void
    {
        /** @var Car $car1 */
        $car1 = Car::factory()->create();
        /** @var Car $car2 */
        $car2 = Car::factory()->create();
        Part::factory()->create(['car_id' => $car1->id]);
        Part::factory()->create(['car_id' => $car1->id]);
        Part::factory()->create(['car_id' => $car2->id]);

        $response = $this->getJson("/api/parts?car_id={$car1->id}");

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(2, $data);
        foreach ($data as $part) {
            assert(is_array($part));
            $car = $part['car'] ?? null;
            assert(is_array($car));
            $this->assertEquals($car1->id, $car['id'] ?? null);
        }
    }

    public function test_can_search_parts_by_car_name(): void
    {
        /** @var Car $car1 */
        $car1 = Car::factory()->create(['name' => 'Toyota']);
        /** @var Car $car2 */
        $car2 = Car::factory()->create(['name' => 'Honda']);
        Part::factory()->create(['car_id' => $car1->id]);
        Part::factory()->create(['car_id' => $car2->id]);

        $response = $this->getJson('/api/parts?search=Toyota');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(1, $data);
        $firstPart = $data[0];
        assert(is_array($firstPart));
        $car = $firstPart['car'] ?? null;
        assert(is_array($car));
        $this->assertEquals('Toyota', $car['name'] ?? null);
    }

    public function test_can_create_part(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        $partData = [
            'name' => 'New Part',
            'serialnumber' => 'SER123',
            'car_id' => $car->id,
        ];

        $response = $this->postJson('/api/parts', $partData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'messages.success.partCreated',
                'data' => [
                    'name' => 'New Part',
                    'serialnumber' => 'SER123',
                ],
            ])
            ->assertJsonStructure(['data' => ['car']]);

        $this->assertDatabaseHas('parts', $partData);
    }

    public function test_cannot_create_part_without_name(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();

        $response = $this->postJson('/api/parts', [
            'serialnumber' => 'SER123',
            'car_id' => $car->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_part_without_serial_number(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();

        $response = $this->postJson('/api/parts', [
            'name' => 'Part Name',
            'car_id' => $car->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serialnumber']);
    }

    public function test_cannot_create_part_without_car(): void
    {
        $response = $this->postJson('/api/parts', [
            'name' => 'Part Name',
            'serialnumber' => 'SER123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    }

    public function test_cannot_create_part_with_invalid_car_id(): void
    {
        $response = $this->postJson('/api/parts', [
            'name' => 'Part Name',
            'serialnumber' => 'SER123',
            'car_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    }

    public function test_cannot_create_part_with_name_exceeding_255_characters(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();

        $response = $this->postJson('/api/parts', [
            'name' => str_repeat('a', 256),
            'serialnumber' => 'SER123',
            'car_id' => $car->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'errors' => [
                    'name' => [
                        'validation.partNameMax:255',
                    ],
                ],
            ]);
    }

    public function test_cannot_create_part_with_serialnumber_exceeding_255_characters(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();

        $response = $this->postJson('/api/parts', [
            'name' => 'Part Name',
            'serialnumber' => str_repeat('A', 256),
            'car_id' => $car->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serialnumber'])
            ->assertJson([
                'errors' => [
                    'serialnumber' => [
                        'validation.serialNumberMax:255',
                    ],
                ],
            ]);
    }

    public function test_can_create_part_with_exactly_255_characters(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        $partData = [
            'name' => str_repeat('a', 255),
            'serialnumber' => str_repeat('B', 255),
            'car_id' => $car->id,
        ];

        $response = $this->postJson('/api/parts', $partData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('parts', $partData);
    }

    public function test_cannot_update_part_with_name_exceeding_255_characters(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create([
            'name' => 'Old Part',
            'car_id' => $car->id,
        ]);

        $response = $this->putJson("/api/parts/{$part->id}", [
            'name' => str_repeat('b', 256),
            'serialnumber' => $part->serialnumber,
            'car_id' => $car->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'errors' => [
                    'name' => [
                        'validation.partNameMax:255',
                    ],
                ],
            ]);
    }

    public function test_cannot_update_part_with_serialnumber_exceeding_255_characters(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create([
            'name' => 'Old Part',
            'car_id' => $car->id,
        ]);

        $response = $this->putJson("/api/parts/{$part->id}", [
            'name' => $part->name,
            'serialnumber' => str_repeat('C', 256),
            'car_id' => $car->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['serialnumber'])
            ->assertJson([
                'errors' => [
                    'serialnumber' => [
                        'validation.serialNumberMax:255',
                    ],
                ],
            ]);
    }

    public function test_can_update_part_with_exactly_255_characters(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car->id]);

        $response = $this->putJson("/api/parts/{$part->id}", [
            'name' => str_repeat('d', 255),
            'serialnumber' => str_repeat('E', 255),
            'car_id' => $car->id,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'name' => str_repeat('d', 255),
            'serialnumber' => str_repeat('E', 255),
        ]);
    }

    public function test_can_show_part_with_car(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car->id]);

        $response = $this->getJson("/api/parts/{$part->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'id' => $part->id,
                    'name' => $part->name,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'car' => ['id', 'name'],
                ],
            ]);

        $this->assertEquals($car->id, $response->json('data.car.id'));
    }

    public function test_can_update_part(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create([
            'name' => 'Old Part',
            'car_id' => $car->id,
        ]);

        $response = $this->putJson("/api/parts/{$part->id}", [
            'name' => 'Updated Part',
            'serialnumber' => $part->serialnumber,
            'car_id' => $car->id,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'messages.success.partUpdated',
                'data' => [
                    'name' => 'Updated Part',
                ],
            ]);

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'name' => 'Updated Part',
        ]);
    }

    public function test_can_update_part_car(): void
    {
        /** @var Car $car1 */
        $car1 = Car::factory()->create();
        /** @var Car $car2 */
        $car2 = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car1->id]);

        $response = $this->putJson("/api/parts/{$part->id}", [
            'name' => $part->name,
            'serialnumber' => $part->serialnumber,
            'car_id' => $car2->id,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'car' => ['id' => $car2->id],
                ],
            ]);

        $this->assertDatabaseHas('parts', [
            'id' => $part->id,
            'car_id' => $car2->id,
        ]);
    }

    public function test_can_delete_part(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car->id]);

        $response = $this->deleteJson("/api/parts/{$part->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => 'messages.success.partDeleted',
            ]);

        $this->assertSoftDeleted('parts', [
            'id' => $part->id,
        ]);
    }

    public function test_deleting_part_does_not_delete_car(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part */
        $part = Part::factory()->create(['car_id' => $car->id]);

        $this->deleteJson("/api/parts/{$part->id}");

        $this->assertSoftDeleted('parts', [
            'id' => $part->id,
        ]);
        $car->refresh();
        $this->assertNull($car->deleted_at);
    }

    public function test_pagination_works_correctly(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        Part::factory()->count(PartController::ITEMS_PER_PAGE + 5)->create(['car_id' => $car->id]);

        $response = $this->getJson('/api/parts');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(PartController::ITEMS_PER_PAGE, $data);
        $this->assertEquals(2, $response->json('last_page'));
    }
}
