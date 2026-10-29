<?php

namespace App\Models;

use Database\Factories\CandidateProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $summary
 * @property string|null $cv_path
 * @property string|null $cv_original_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['summary', 'cv_path', 'cv_original_name'])]
#[Hidden(['cv_path'])]
class CandidateProfile extends Model
{
    /** @use HasFactory<CandidateProfileFactory> */
    use HasFactory;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::deleting(fn (CandidateProfile $profile) => $profile->deleteCv());
    }

    /**
     * Get the candidate who owns the profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether a CV has been uploaded.
     */
    public function hasCv(): bool
    {
        return $this->cv_path !== null;
    }

    /**
     * Delete the stored CV file, if any.
     */
    public function deleteCv(): void
    {
        if ($this->cv_path !== null) {
            Storage::delete($this->cv_path);
        }
    }

    /**
     * Get the profile data exposed to the frontend.
     *
     * @return array{summary: string|null, cv_name: string|null, has_cv: bool, updated_at: Carbon|null}
     */
    public function toPublicArray(): array
    {
        return [
            'summary' => $this->summary,
            'cv_name' => $this->cv_original_name,
            'has_cv' => $this->hasCv(),
            'updated_at' => $this->updated_at,
        ];
    }
}
