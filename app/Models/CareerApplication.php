<?php

namespace App\Models;

use Database\Factories\CareerApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerApplication extends Model
{
    /** @use HasFactory<CareerApplicationFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'files' => 'array',
        ];
    }

    public function careerOpening(): BelongsTo
    {
        return $this->belongsTo(CareerOpening::class);
    }
}
