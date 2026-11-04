<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'phone', 'password', 'pending_email'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    public const STATUS_EMAIL_NON_VERIFIE = 'email_non_verifie';

    public const STATUS_EN_ATTENTE_VALIDATION = 'en_attente_validation';

    public const STATUS_ACTIF = 'actif';

    public const STATUS_REFUSE = 'refuse';

    public const STATUS_DESACTIVE = 'desactive';

    public const STATUSES = [
        self::STATUS_EMAIL_NON_VERIFIE,
        self::STATUS_EN_ATTENTE_VALIDATION,
        self::STATUS_ACTIF,
        self::STATUS_REFUSE,
        self::STATUS_DESACTIVE,
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->status !== null && ! in_array($user->status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Le statut du compte est invalide.',
                ]);
            }
        });
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : strtolower(trim($value)),
        );
    }

    protected function pendingEmail(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null || trim($value) === '' ? null : strtolower(trim($value)),
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

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
        ];
    }
}
