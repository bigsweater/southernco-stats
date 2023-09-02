<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScDailyReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'day_at' => 'immutable_datetime',
        'alert_cost' => 'float',
        'alert_kwh' => 'float',
        'average_daily_cost_usd' => 'float',
        'overage_low_kwh' => 'float',
        'overage_high_kwh' => 'float',
        'weekday_cost_usd' => 'float',
        'weekday_usage_kwh' => 'float',
        'weekend_cost_usd' => 'float',
        'weekend_usage_kwh' => 'float',
        'temp_high_f' => 'float',
        'temp_low_f' => 'float',
    ];

    public function scAccount(): BelongsTo
    {
        return $this->belongsTo(ScAccount::class);
    }
}
