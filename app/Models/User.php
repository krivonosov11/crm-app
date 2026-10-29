<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property CandidateStatus|null $status
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CandidateProfile|null $candidateProfile
 */
#[Fillable(['name', 'email', 'role', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if (! $user->isCandidate()) {
                $user->status = null;
            } elseif ($user->status === null) {
                $user->status = CandidateStatus::Created;
            }
        });

        static::deleting(fn (User $user) => $user->candidateProfile?->delete());
    }

    /**
     * Get the candidate profile (summary and CV) of the user.
     *
     * @return HasOne<CandidateProfile, $this>
     */
    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
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
            'role' => UserRole::class,
            'status' => CandidateStatus::class,
        ];
    }

    /**
     * Scope a query to only include candidates.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function candidates(Builder $query): void
    {
        $query->where('role', UserRole::Candidate);
    }

    /**
     * Determine whether the user has the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determine whether the user has the HR role.
     */
    public function isHr(): bool
    {
        return $this->role === UserRole::Hr;
    }

    /**
     * Determine whether the user has the candidate role.
     */
    public function isCandidate(): bool
    {
        return $this->role === UserRole::Candidate;
    }
}
