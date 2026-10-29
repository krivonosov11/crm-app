<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCandidateProfileRequest;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CandidateProfileController extends Controller
{
    /**
     * Show the authenticated candidate's profile form.
     */
    public function edit(Request $request): Response
    {
        Gate::authorize('manageOwn', CandidateProfile::class);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('profile/edit', [
            'profile' => $user->candidateProfile?->toPublicArray(),
            'cvUrl' => route('users.cv', $user),
            'maxCvKilobytes' => UpdateCandidateProfileRequest::MAX_CV_KILOBYTES,
        ]);
    }

    /**
     * Update the authenticated candidate's summary and CV.
     */
    public function update(UpdateCandidateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $profile = $user->candidateProfile()->firstOrNew();
        $profile->summary = $request->validated('summary');

        if ($request->hasFile('cv') || $request->boolean('remove_cv')) {
            $profile->deleteCv();
            $profile->cv_path = null;
            $profile->cv_original_name = null;
        }

        if ($request->hasFile('cv')) {
            $file = $request->file('cv');
            $path = $file->store('cvs');

            if ($path === false) {
                throw ValidationException::withMessages(['cv' => 'The CV could not be stored. Please try again.']);
            }

            $profile->cv_path = $path;
            $profile->cv_original_name = $file->getClientOriginalName();
        }

        $profile->save();

        Inertia::flash('success', 'Profile saved.');

        return to_route('profile.edit');
    }
}
