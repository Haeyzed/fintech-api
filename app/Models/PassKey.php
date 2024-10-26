<?php

namespace App\Models;

use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassKey extends Model
{
    /** @use HasFactory<\Database\Factories\PassKeyFactory> */
    use HasFactory, HasSqid, HasDateFilter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> $fillable
     */
    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'public_key',
        'sign_count',
        'last_used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'json',
            'credential_id' => 'encrypted',
            'public_key' => 'encrypted',
            'sign_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the blocked IP.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
