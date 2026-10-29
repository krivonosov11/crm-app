<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateCvController extends Controller
{
    /**
     * Download the CV of the given candidate.
     */
    public function __invoke(User $user): StreamedResponse
    {
        $profile = $user->candidateProfile;

        abort_if($profile === null || ! $profile->hasCv(), 404);

        Gate::authorize('view', $profile);

        return Storage::download((string) $profile->cv_path, $profile->cv_original_name);
    }
}
