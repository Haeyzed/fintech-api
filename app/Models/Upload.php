<?php

namespace App\Models;

use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    /** @use HasFactory<\Database\Factories\UploadFactory> */
    use HasSqid, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'disk',
        'provider',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Get the user that owns the upload.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
