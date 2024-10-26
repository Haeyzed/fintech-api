<?php

namespace App\Models;

use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlockedIp extends Model
{
    /** @use HasFactory<\Database\Factories\BlockedIpFactory> */
    use HasSqid, HasDateFilter, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'reason',
        'blocked_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'blocked_until' => 'datetime',
    ];

    /**
     * Get the user that owns the blocked IP.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
