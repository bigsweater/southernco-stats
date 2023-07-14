<?php

namespace App\Models;

use App\Holidays;
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
            get: function (): bool {
                if (
                    is_null($this->peak_hours_from)
                    || is_null($this->peak_hours_to)
                ) {
                    return false;
                }

                if ($this->hour_at->isWeekend()) {
                    return false;
                }

                if (
                    $this->hour_at->isSameDay(Holidays::independenceDay($this->hour_at->year))
                    || $this->hour_at->isSameDay(Holidays::laborDay($this->hour_at->year))
                ) {
                    return false;
                }

                return $this->hour_at->hour >= $this->peak_hours_from
                    && $this->hour_at->hour < $this->peak_hours_to;
            }
        );
    }

    public function scAccount(): BelongsTo
    {
        return $this->belongsTo(ScAccount::class);
    }
}
