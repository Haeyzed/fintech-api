<?php

namespace App\Models;

use App\Enums\BankTypeEnum;
use App\Enums\GatewayEnum;
use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Bank extends Model
{
    use HasDateFilter, HasSqid, SoftDeletes, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'country_id',
        'currency_id',
        'name',
        'code',
        'slug',
        'long_code',
        'gateway',
        'pay_with_bank',
        'is_active',
        'type',
        'ussd',
        'logo'
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'country_id' => 'integer',
        'currency_id' => 'integer',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'pay_with_bank' => 'boolean',
        'type' => BankTypeEnum::class,
        'gateway' => GatewayEnum::class,
    ];

    /**
     * Automatically generate a slug when creating a new bank.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($bank) {
            if (empty($bank->slug)) {
                $slug = Str::slug($bank->name);
                $originalSlug = $slug;
                $count = 1;

                while (self::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $bank->slug = $slug;
            }
        });
    }


    /**
     * Get the country that owns the bank.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the currency that owns the bank.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
