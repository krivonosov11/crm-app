<?php

namespace App\Policies;

use App\Models\CandidateProfile;
use App\Models\User;

class CandidateProfilePolicy
{
    /**
     * Determine whether the user can view the profile (summary and CV).
     *
     * Admins and HR can view every candidate profile; candidates only their own.
     */
    public function view(User $user, CandidateProfile $profile): bool
    {
        return $user->isAdmin() || $user->isHr() || $user->id === $profile->user_id;
    }

    /**
     * Determine whether the user can manage their own candidate profile.
     */
    public function manageOwn(User $user): bool
    {
        return $user->isCandidate();
    }
}
