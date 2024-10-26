<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasSqid, HasDateFilter, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'payment_method_id',
        'type',
        'amount',
        'status',
        'description',
        'reference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bank account associated with the transaction.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the payment method associated with the transaction.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the transaction's amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }
}
