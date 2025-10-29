<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\CarController;
use App\Models\Car;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_cars(): void
    {
        Car::factory()->count(3)->create();

        $response = $this->getJson('/api/cars');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'registration_number', 'is_registered', 'parts'],
                ],
                'current_page',
                'last_page',
            ]);

        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(3, $data);
    }

    public function test_can_filter_cars_by_search(): void
    {
        Car::factory()->create(['name' => 'Toyota Corolla']);
        Car::factory()->create(['name' => 'Honda Civic']);
        Car::factory()->create(['name' => 'BMW X5']);

        $response = $this->getJson('/api/cars?search=Toyota');

        $response->assertStatus(200);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(1, $data);
        $firstCar = $data[0];
        assert(is_array($firstCar));
        $this->assertEquals('Toyota Corolla', $firstCar['name'] ?? null);
    }

    public function test_can_filter_cars_by_registration_number(): void
    {
        Car::factory()->create([
            'name' => 'Car 1',
            'registration_number' => 'AB123CD',
        ]);
        Car::factory()->create([
            'name' => 'Car 2',
            'registration_number' => 'XY789ZW',
        ]);

        $response = $this->getJson('/api/cars?search=AB123');

        $response->assertStatus(200);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(1, $data);
        $firstCar = $data[0];
        assert(is_array($firstCar));
        $this->assertEquals('AB123CD', $firstCar['registration_number'] ?? null);
    }

    public function test_can_filter_cars_by_is_registered(): void
    {
        Car::factory()->create(['is_registered' => true]);
        Car::factory()->create(['is_registered' => false]);
        Car::factory()->create(['is_registered' => true]);

        $response = $this->getJson('/api/cars?is_registered=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(2, $data);
        foreach ($data as $car) {
            assert(is_array($car));
            $this->assertTrue($car['is_registered'] ?? false);
        }
    }

    public function test_can_get_all_cars_for_dropdown(): void
    {
        Car::factory()->count(5)->create();

        $response = $this->getJson('/api/cars-all');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_can_create_car(): void
    {
        $carData = [
            'name' => 'New Car',
            'is_registered' => true,
            'registration_number' => 'AB123CD',
        ];

        $response = $this->postJson('/api/cars', $carData);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'New Car',
                'is_registered' => true,
                'registration_number' => 'AB123CD',
            ]);

        $this->assertDatabaseHas('cars', $carData);
    }

    public function test_cannot_create_car_without_name(): void
    {
        $response = $this->postJson('/api/cars', [
            'is_registered' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_registered_car_without_registration_number(): void
    {
        $response = $this->postJson('/api/cars', [
            'name' => 'Test Car',
            'is_registered' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);
    }

    public function test_can_create_unregistered_car_without_registration_number(): void
    {
        $carData = [
            'name' => 'Unregistered Car',
            'is_registered' => false,
        ];

        $response = $this->postJson('/api/cars', $carData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cars', $carData);
    }

    public function test_cannot_create_car_with_invalid_registration_number_format(): void
    {
        $response = $this->postJson('/api/cars', [
            'name' => 'Test Car',
            'is_registered' => true,
            'registration_number' => 'INVALID123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);

        $errors = $response->json('errors.registration_number');
        assert(is_array($errors));
        $this->assertContains('validation.registrationNumberFormat', $errors);
    }

    public function test_cannot_create_car_with_registration_number_too_long(): void
    {
        $response = $this->postJson('/api/cars', [
            'name' => 'Test Car',
            'is_registered' => true,
            'registration_number' => 'AB1234CD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);
    }

    public function test_cannot_create_car_with_registration_number_too_short(): void
    {
        $response = $this->postJson('/api/cars', [
            'name' => 'Test Car',
            'is_registered' => true,
            'registration_number' => 'AB12CD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);
    }

    public function test_cannot_create_car_with_lowercase_registration_number(): void
    {
        $response = $this->postJson('/api/cars', [
            'name' => 'Test Car',
            'is_registered' => true,
            'registration_number' => 'ab123cd',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);

        $errors = $response->json('errors.registration_number');
        assert(is_array($errors));
        $this->assertContains('validation.registrationNumberFormat', $errors);
    }

    public function test_can_create_car_with_valid_registration_number_format(): void
    {
        $carData = [
            'name' => 'Valid Car',
            'is_registered' => true,
            'registration_number' => 'XY789ZW',
        ];

        $response = $this->postJson('/api/cars', $carData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cars', $carData);
    }

    public function test_cannot_update_car_with_invalid_registration_number_format(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create([
            'name' => 'Old Car',
            'is_registered' => true,
            'registration_number' => 'AB123CD',
        ]);

        $response = $this->putJson("/api/cars/{$car->id}", [
            'name' => 'Updated Car',
            'is_registered' => true,
            'registration_number' => 'INVALID',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);

        $errors = $response->json('errors.registration_number');
        assert(is_array($errors));
        $this->assertContains('validation.registrationNumberFormat', $errors);
    }

    public function test_cannot_update_car_with_registration_number_too_long(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create([
            'name' => 'Old Car',
            'is_registered' => true,
            'registration_number' => 'AB123CD',
        ]);

        $response = $this->putJson("/api/cars/{$car->id}", [
            'name' => 'Updated Car',
            'is_registered' => true,
            'registration_number' => 'AB1234CD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['registration_number']);
    }

    public function test_can_update_car_with_valid_registration_number_format(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create([
            'name' => 'Old Car',
            'is_registered' => true,
            'registration_number' => 'AB123CD',
        ]);

        $response = $this->putJson("/api/cars/{$car->id}", [
            'name' => 'Updated Car',
            'is_registered' => true,
            'registration_number' => 'MN456OP',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'registration_number' => 'MN456OP',
            ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'registration_number' => 'MN456OP',
        ]);
    }

    public function test_can_show_car_with_parts(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part1 */
        $part1 = Part::factory()->create(['car_id' => $car->id]);
        /** @var Part $part2 */
        $part2 = Part::factory()->create(['car_id' => $car->id]);

        $response = $this->getJson("/api/cars/{$car->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $car->id,
                'name' => $car->name,
            ])
            ->assertJsonStructure([
                'parts' => [
                    '*' => ['id', 'name', 'serialnumber'],
                ],
            ]);

        $parts = $response->json('parts');
        assert(is_array($parts));
        $this->assertCount(2, $parts);
    }

    public function test_can_update_car(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create([
            'name' => 'Old Name',
            'is_registered' => false,
        ]);

        $response = $this->putJson("/api/cars/{$car->id}", [
            'name' => 'New Name',
            'is_registered' => true,
            'registration_number' => 'UP123DL',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'New Name',
                'is_registered' => true,
                'registration_number' => 'UP123DL',
            ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'name' => 'New Name',
            'is_registered' => true,
        ]);
    }

    public function test_can_delete_car(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();

        $response = $this->deleteJson("/api/cars/{$car->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => __('Car successfully deleted'),
            ]);

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    }

    public function test_deleting_car_cascades_to_parts(): void
    {
        /** @var Car $car */
        $car = Car::factory()->create();
        /** @var Part $part1 */
        $part1 = Part::factory()->create(['car_id' => $car->id]);
        /** @var Part $part2 */
        $part2 = Part::factory()->create(['car_id' => $car->id]);

        $this->deleteJson("/api/cars/{$car->id}");

        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
        $this->assertDatabaseMissing('parts', ['id' => $part1->id]);
        $this->assertDatabaseMissing('parts', ['id' => $part2->id]);
    }

    public function test_pagination_works_correctly(): void
    {
        Car::factory()->count(CarController::ITEMS_PER_PAGE + 5)->create();

        $response = $this->getJson('/api/cars');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);

        $data = $response->json('data');
        assert(is_array($data));
        $this->assertCount(CarController::ITEMS_PER_PAGE, $data);
        $this->assertEquals(2, $response->json('last_page'));
    }
}
