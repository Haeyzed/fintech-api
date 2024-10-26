<?php

namespace App\Models;

use App\Traits\HasDateFilter;
use App\Traits\HasSqid;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasSqid, HasDateFilter, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string> $fillable
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'password',
        'pin',
        'otp',
        'balance',
        'last_login_at',
        'current_login_at',
        'last_login_ip',
        'current_login_ip',
        'login_count',
        'provider',
        'provider_id',
        'profile_image',
        'google2fa_secret',
        'google2fa_enabled',
        'two_factor_recovery_codes',
    ];

    /**
     * Boot the model and its traits.
     *
     * This method is used to hook into the model's lifecycle events. In this case,
     * it sets the 'pin' attribute to '000000' if it's empty during the creation of a new user.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        /**
         * Handle the "creating" event.
         *
         * This ensures that if the 'pin' field is empty, it will be set to '000000' before saving the user.
         *
         * @param User $user The user instance being created.
         * @return void
         */
        static::creating(function ($user) {
            if (empty($user->pin)) {
                $user->pin = '000000';
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string> $hidden
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'last_login_at' => 'datetime',
            'current_login_at' => 'datetime',
            'google2fa_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'array',
        ];
    }

    /**
     * Get the blocked IPs associated with the user.
     *
     * @return HasMany
     */
    public function blockedIps(): HasMany
    {
        return $this->hasMany(BlockedIp::class);
    }

    /**
     * Get the pass keys associated with the user.
     *
     * @return HasMany
     */
    public function passKeys(): HasMany
    {
        return $this->hasMany(PassKey::class);
    }

    /**
     * Get the device tokens associated with the user.
     *
     * @return HasMany
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Get the uploads associated with the user.
     *
     * @return HasMany
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    /**
     * Get the bank accounts associated with the user.
     *
     * @return HasMany
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Get the payment methods associated with the user.
     *
     * @return HasMany
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Get the transactions associated with the user.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Add or update a device token for the user.
     *
     * @param string $token
     * @param array $deviceInfo
     * @return void
     */
    public function addOrUpdateDeviceToken(string $token, array $deviceInfo): void
    {
        $this->deviceTokens()->updateOrCreate(
            ['token' => $token],
            [
                'device_type' => $deviceInfo['device_type'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'app_version' => $deviceInfo['app_version'] ?? null,
            ]
        );
    }

    /**
     * Encrypt the Google 2FA secret before storing it.
     *
     * @param string|null $value
     * @return void
     */
    public function setGoogle2faSecretAttribute(?string $value): void
    {
        if ($value !== null) {
            $this->attributes['google2fa_secret'] = encrypt($value);
        } else {
            $this->attributes['google2fa_secret'] = null;
        }
    }

    /**
     * Decrypt the Google 2FA secret when retrieving it.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getGoogle2faSecretAttribute(?string $value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Generate new recovery codes for the user.
     *
     * @return array
     */
    public function generateRecoveryCodes(): array
    {
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = Str::random(10);
        }
        $this->two_factor_recovery_codes = $recoveryCodes;
        $this->save();

        return $recoveryCodes;
    }

    /**
     * Verify a recovery code.
     *
     * @param string $recoveryCode
     * @return bool
     */
    public function verifyRecoveryCode(string $recoveryCode): bool
    {
        $recoveryCodes = $this->two_factor_recovery_codes;
        $key = array_search($recoveryCode, $recoveryCodes);

        if ($key !== false) {
            unset($recoveryCodes[$key]);
            $this->two_factor_recovery_codes = array_values($recoveryCodes);
            $this->save();
            return true;
        }

        return false;
    }
}
