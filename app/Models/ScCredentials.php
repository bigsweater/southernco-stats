<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScCredentials extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'username' => 'encrypted',
        'password' => 'encrypted',
        'jwt' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
