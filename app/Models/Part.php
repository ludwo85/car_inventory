<?php

namespace App\Models;

use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $serialnumber
 * @property int $car_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'serialnumber',
        'car_id',
    ];

    /**
     * @phpstan-return BelongsTo<Car, $this>
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * @return PartFactory
     */
    protected static function newFactory(): PartFactory
    {
        return PartFactory::new();
    }
}
