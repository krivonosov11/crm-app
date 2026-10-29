<?php

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

test('candidates can view their profile page', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('profile/edit')
            ->where('profile', null));
});

test('candidates can save a summary and upload a CV', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)
        ->post(route('profile.update'), [
            'summary' => 'Senior PHP developer.',
            'cv' => UploadedFile::fake()->create('my-cv.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('profile.edit'));

    $profile = $candidate->candidateProfile()->firstOrFail();

    expect($profile->summary)->toBe('Senior PHP developer.')
        ->and($profile->cv_original_name)->toBe('my-cv.pdf');

    Storage::assertExists((string) $profile->cv_path);
});

test('uploading a new CV replaces the old file', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->post(route('profile.update'), [
        'cv' => UploadedFile::fake()->create('old.pdf', 10, 'application/pdf'),
    ]);
    $oldPath = (string) $candidate->candidateProfile()->value('cv_path');

    $this->actingAs($candidate)->post(route('profile.update'), [
        'summary' => 'Updated summary',
        'cv' => UploadedFile::fake()->create('new.docx', 10),
    ]);

    $profile = $candidate->candidateProfile()->firstOrFail();

    Storage::assertMissing($oldPath);
    Storage::assertExists((string) $profile->cv_path);
    expect($profile->cv_original_name)->toBe('new.docx')
        ->and($profile->summary)->toBe('Updated summary');
});

test('candidates can update the summary while keeping their CV', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->post(route('profile.update'), [
        'summary' => 'First',
        'cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
    ]);

    $this->actingAs($candidate)->post(route('profile.update'), ['summary' => 'Second']);

    $profile = $candidate->candidateProfile()->firstOrFail();

    expect($profile->summary)->toBe('Second')
        ->and($profile->hasCv())->toBeTrue();
});

test('candidates can remove their CV', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->post(route('profile.update'), [
        'cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
    ]);
    $path = (string) $candidate->candidateProfile()->value('cv_path');

    $this->actingAs($candidate)->post(route('profile.update'), ['remove_cv' => true]);

    Storage::assertMissing($path);
    expect($candidate->candidateProfile()->firstOrFail()->hasCv())->toBeFalse();
});

test('cv uploads are validated', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)
        ->post(route('profile.update'), [
            'summary' => str_repeat('a', 2001),
            'cv' => UploadedFile::fake()->create('virus.exe', 10),
        ])
        ->assertSessionHasErrors(['summary', 'cv']);

    $this->actingAs($candidate)
        ->post(route('profile.update'), [
            'cv' => UploadedFile::fake()->create('huge.pdf', 3000, 'application/pdf'),
        ])
        ->assertSessionHasErrors('cv');
});

test('only candidates have a profile page', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get(route('profile.edit'))->assertForbidden();
    $this->actingAs($user)->post(route('profile.update'), ['summary' => 'x'])->assertForbidden();
})->with(['admin', 'hr']);

test('hr and admins can view a candidate profile and download the CV', function (string $state) {
    $viewer = User::factory()->{$state}()->create();
    $profile = CandidateProfile::factory()->withCv()->create();
    Storage::put((string) $profile->cv_path, 'cv-content');

    $this->actingAs($viewer)
        ->get(route('users.show', $profile->user))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/show')
            ->where('profile.summary', $profile->summary)
            ->where('profile.has_cv', true)
            ->where('cvUrl', route('users.cv', $profile->user))
            ->missing('profile.cv_path'));

    $this->actingAs($viewer)
        ->get(route('users.cv', $profile->user))
        ->assertOk()
        ->assertDownload('cv.pdf');
})->with(['admin', 'hr']);

test('the users list shows whether a candidate has a CV', function () {
    $admin = User::factory()->admin()->create();
    $profile = CandidateProfile::factory()->withCv()->create();

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => $profile->user->email]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data.0.has_cv', true));
});

test('candidates can download their own CV but not others', function () {
    $own = CandidateProfile::factory()->withCv()->create();
    $other = CandidateProfile::factory()->withCv()->create();
    Storage::put((string) $own->cv_path, 'mine');
    Storage::put((string) $other->cv_path, 'theirs');

    $this->actingAs($own->user)->get(route('users.cv', $own->user))->assertOk();
    $this->actingAs($own->user)->get(route('users.cv', $other->user))->assertForbidden();
    $this->actingAs($own->user)->get(route('users.show', $other->user))->assertForbidden();
});

test('hr cannot view non-candidate users', function () {
    $hr = User::factory()->hr()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($hr)->get(route('users.show', $admin))->assertForbidden();
});

test('deleting a candidate removes their CV file', function () {
    $admin = User::factory()->admin()->create();
    $profile = CandidateProfile::factory()->withCv()->create();
    Storage::put((string) $profile->cv_path, 'cv-content');

    $this->actingAs($admin)->delete(route('users.destroy', $profile->user));

    Storage::assertMissing((string) $profile->cv_path);
    $this->assertModelMissing($profile);
});

test('candidate dashboard includes their profile', function () {
    $profile = CandidateProfile::factory()->create(['summary' => 'Hello HR']);

    $this->actingAs($profile->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('candidateProfile.summary', 'Hello HR'));
});
