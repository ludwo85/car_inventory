<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartRequest;
use App\Http\Requests\UpdatePartRequest;
use App\Models\Part;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Part::with('car');

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            if (is_string($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('serialnumber', 'like', "%{$search}%")
                      ->orWhereHas('car', function ($carQuery) use ($search) {
                          $carQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }
        }

        if ($request->has('car_id') && $request->input('car_id') !== '') {
            $query->where('car_id', $request->input('car_id'));
        }

        $parts = $query->orderBy('name')->paginate(10);

        return response()->json($parts);
    }

    public function store(StorePartRequest $request): JsonResponse
    {
        $part = new Part();
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $part->fill($validated);
        $part->save();

        return response()->json($part->load('car'), 201);
    }

    public function show(Part $part): JsonResponse
    {
        return response()->json($part->load('car'));
    }

    public function update(UpdatePartRequest $request, Part $part): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $part->update($validated);

        return response()->json($part->load('car'));
    }

    public function destroy(Part $part): JsonResponse
    {
        $part->delete();

        return response()->json(['message' => __('Part successfully deleted')]);
    }
}
