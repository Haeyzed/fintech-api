<?php

namespace App\Models;

use App\Enums\AccountTypeEnum;
use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory, HasSqid, HasDateFilter, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'account_number',
        'bank_name',
        'account_type',
        'balance',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'is_primary' => 'boolean',
        'account_type' => AccountTypeEnum::class
    ];

    /**
     * Get the user that owns the bank account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions associated with the bank account.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
