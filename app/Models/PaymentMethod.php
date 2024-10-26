<?php

namespace App\Models;

use App\Enums\PaymentMethodTypeEnum;
use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
    use HasSqid, HasDateFilter, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> $fillable
     */
    protected $fillable = [
        'type', // e.g., 'credit_card', 'paypal', 'stripe'
        'details', // JSON field to store payment method details
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string> $hidden
     */
    protected $casts = [
        'details' => 'array',
        'is_active' => 'boolean',
        'type' => PaymentMethodTypeEnum::class,
    ];

    /**
     * Get the transactions associated with the payment method.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
