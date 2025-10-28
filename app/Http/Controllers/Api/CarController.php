<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Car::with('parts');

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            if (is_string($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('registration_number', 'like', "%{$search}%");
                });
            }
        }

        if ($request->has('is_registered') && $request->input('is_registered') !== '') {
            $query->where('is_registered', $request->boolean('is_registered'));
        }

        $cars = $query->orderBy('name')->paginate(10);

        return response()->json($cars);
    }

    public function all(): JsonResponse
    {
        $cars = Car::orderBy('name')->get();
        return response()->json($cars);
    }

    public function store(StoreCarRequest $request): JsonResponse
    {
        $car = new Car();
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $car->fill($validated);
        $car->save();

        return response()->json($car, 201);
    }

    public function show(Car $car): JsonResponse
    {
        return response()->json($car->load('parts'));
    }

    public function update(UpdateCarRequest $request, Car $car): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $car->update($validated);

        return response()->json($car->load('parts'));
    }

    public function destroy(Car $car): JsonResponse
    {
        $car->delete();

        return response()->json(['message' => __('Car successfully deleted')]);
    }
}
