<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScHourlyReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'hour_at' => 'immutable_datetime',
        'cost_usd' => 'float',
        'usage_kwh' => 'float',
        'temp_f' => 'float',
        'peak_hours_from' => 'integer',
        'peak_hours_to' => 'integer',
    ];

    public function isPeak(): Attribute
    {
        return Attribute::make(
            get: fn () => true
        );
    }

    public function scAccount(): BelongsTo
    {
        return $this->belongsTo(ScAccount::class);
    }
}
