<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function fromApiResponse(array $data): static
    {
        return new static([
            'account_number' => $data['AccountNumber'],
            'account_type' => $data['AccountType'],
            'company' => $data['Company'],
            'is_primary' => $data['PrimaryAccount'] === 'Y' ? true : false,
            'description' => $data['Description']
        ]);

    }
}
