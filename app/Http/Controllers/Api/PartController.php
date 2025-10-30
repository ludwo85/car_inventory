<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Models\Part;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartController extends Controller
{
    public const ITEMS_PER_PAGE = 15;

    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Part> $query */
            $query = Part::withTrashed()->with(['car' => function ($query): void {
                /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Car, \App\Models\Part> $query */
                $query->withTrashed();
            }]);

            if ($request->has('search') && $request->input('search')) {
                $search = $request->input('search');
                if (is_string($search)) {
                    $query->where(function (Builder $q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('serialnumber', 'like', "%{$search}%")
                          ->orWhereHas('car', function (Builder $carQuery) use ($search) {
                              $carQuery
                                ->withoutGlobalScope(SoftDeletingScope::class)
                                ->where('name', 'like', "%{$search}%");
                          });
                    });
                }
            }

            if ($request->has('car_id') && $request->input('car_id') !== '') {
                $query->where('car_id', $request->input('car_id'));
            }

            /** @var string $sortBy */
            $sortBy = $request->input('sort_by', 'name');
            /** @var string $sortDirection */
            $sortDirection = $request->input('sort_direction', 'asc');

            $allowedSortFields = ['name', 'serialnumber', 'created_at', 'car_name'];
            if (in_array($sortBy, $allowedSortFields)) {
                if ($sortBy === 'car_name') {
                    $query->join('cars', 'parts.car_id', '=', 'cars.id')
                          ->orderBy('cars.name', $sortDirection)
                          ->select('parts.*');
                } else {
                    $query->orderBy($sortBy, $sortDirection);
                }
            } else {
                $query->orderBy('name', 'asc');
            }

            $parts = $query->paginate(self::ITEMS_PER_PAGE);

            return response()->json($parts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.retrieveParts',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StorePartRequest $request): JsonResponse
    {
        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();
            $part = new Part();
            $part->fill($validated);
            $part->save();
            return response()->json([
                'success' => true,
                'message' => 'messages.success.partCreated',
                'data' => $part->load(['car' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Car, \App\Models\Part> $query */
                    $query->withTrashed();
                }]),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.createPart',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Part $part): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $part->load(['car' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Car, \App\Models\Part> $query */
                    $query->withTrashed();
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.retrievePart',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdatePartRequest $request, Part $part): JsonResponse
    {
        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();
            $part->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'messages.success.partUpdated',
                'data' => $part->load(['car' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Car, \App\Models\Part> $query */
                    $query->withTrashed();
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.updatePart',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Part $part): JsonResponse
    {
        try {
            $part->delete();
            return response()->json([
                'success' => true,
                'message' => 'messages.success.partDeleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.deletePart',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            /** @var Part|null $part */
            $part = Part::withTrashed()->find($id);

            if (!$part) {
                return response()->json([
                    'error' => true,
                    'message' => 'messages.error.partNotFound',
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$part->trashed()) {
                return response()->json([
                    'error' => true,
                    'message' => 'messages.error.partNotDeleted',
                ], Response::HTTP_BAD_REQUEST);
            }

            $part->restore();
            return response()->json([
                'success' => true,
                'message' => 'messages.success.partRestored',
                'data' => $part->load(['car' => function ($query) {
                    /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Car, \App\Models\Part> $query */
                    $query->withTrashed();
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'messages.error.restorePart',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
