<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScMonthlyReport extends Model
{
    use HasFactory;

    protected $casts = [
        'period_start_at' => 'immutable_datetime',
        'period_end_at' => 'immutable_datetime',
        'cost_usd' => 'float',
        'usage_kwh' => 'float',
        'temp_high_f' => 'float',
        'temp_low_f' => 'float',
    ];

    protected $guarded = [];

    public function scAccount(): BelongsTo
    {
        return $this->belongsTo(ScAccount::class);
    }
}
