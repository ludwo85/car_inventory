<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CarController extends Controller
{
    public const ITEMS_PER_PAGE = 15;

    public function index(Request $request): JsonResponse
    {
        try {

            /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Car> $query */
            $query = Car::withTrashed()
            ->with(['parts' => function ($query): void {
                /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Part, \App\Models\Car> $query */
                $query->withTrashed();
            }])
            ->withCount(['parts' => function ($query): void {
                /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Part, \App\Models\Car> $query */
                $query->withoutTrashed();
            }]);

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

            /** @var string $sortBy */
            $sortBy = $request->input('sort_by', 'name');
            /** @var string $sortDirection */
            $sortDirection = $request->input('sort_direction', 'asc');

            $allowedSortFields = ['name', 'registration_number', 'is_registered', 'created_at', 'parts_count'];
            if (in_array($sortBy, $allowedSortFields)) {
                if ($sortBy === 'parts_count') {
                    $query->orderBy('parts_count', $sortDirection);
                } else {
                    $query->orderBy($sortBy, $sortDirection);
                }
            } else {
                $query->orderBy('name', 'asc');
            }

            $cars = $query->paginate(self::ITEMS_PER_PAGE);

            return response()->json($cars);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.retrieveCars',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function all(): JsonResponse
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Car> $cars */
            $cars = Car::withTrashed()->orderBy('name')->get();
            return response()->json($cars);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.retrieveCars',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StoreCarRequest $request): JsonResponse
    {
        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();
            $car = Car::create($validated);
            return response()->json([
                'success' => true,
                'message' => 'messages.success.carCreated',
                'data' => $car,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.createCar',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Car $car): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $car->load(['parts' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Part, \App\Models\Car> $query */
                    $query->withTrashed();
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.retrieveCar',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateCarRequest $request, Car $car): JsonResponse
    {
        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();
            $car->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'messages.success.carUpdated',
                'data' => $car->load(['parts' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Part, \App\Models\Car> $query */
                    $query->withTrashed();
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.updateCar',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Car $car): JsonResponse
    {
        try {
            $car->delete();
            return response()->json([
                'success' => true,
                'message' => 'messages.success.carDeleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.deleteCar',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            /** @var Car|null $car */
            $car = Car::withTrashed()->find($id);

            if (!$car) {
                return response()->json([
                    'error' => true,
                    'message' => 'messages.error.carNotFound',
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$car->trashed()) {
                return response()->json([
                    'error' => true,
                    'message' => 'messages.error.carNotDeleted',
                ], Response::HTTP_BAD_REQUEST);
            }

            $car->restore();
            return response()->json([
                'success' => true,
                'message' => 'messages.success.carRestored',
                'data' => $car->load(['parts' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Part, \App\Models\Car> $query */
                    $query->withTrashed();
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.restoreCar',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
