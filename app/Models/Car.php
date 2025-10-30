<?php

namespace App\Models;

use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $registration_number
 * @property bool $is_registered
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin Builder<Car>
 */
class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'registration_number',
        'is_registered',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_registered' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Car $car) {
            if ($car->isForceDeleting()) {
                return;
            }
            $car->parts()->delete();
        });

        static::restoring(function (Car $car) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Part> $parts */
            $parts = Part::onlyTrashed()->where('car_id', $car->id)->get();
            foreach ($parts as $part) {
                $part->restore();
            }
        });
    }

    /**
     * @phpstan-return HasMany<Part, $this>
     */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }

    /**
     * @return CarFactory
     */
    protected static function newFactory(): CarFactory
    {
        return CarFactory::new();
    }
}
