<?php

use App\Enums\CandidateStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins see users per role', function () {
    $admin = User::factory()->admin()->create();
    User::factory(2)->hr()->create();
    User::factory(3)->candidate()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats', [
                ['label' => 'Total users', 'value' => 6],
                ['label' => 'Admin', 'value' => 1],
                ['label' => 'HR', 'value' => 2],
                ['label' => 'Candidate', 'value' => 3],
            ])
            ->has('recentUsers', 5)
            ->missing('profile'));
});

test('hr users see candidates per status', function () {
    $hr = User::factory()->hr()->create();
    User::factory()->admin()->create();
    User::factory(2)->candidate()->create();
    User::factory()->withStatus(CandidateStatus::Approved)->create();

    $this->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats', [
                ['label' => 'Candidates', 'value' => 3],
                ['label' => 'Created', 'value' => 2],
                ['label' => 'Processing', 'value' => 0],
                ['label' => 'Approved', 'value' => 1],
                ['label' => 'Rejected', 'value' => 0],
            ])
            ->has('recentUsers', 3)
            ->where('recentUsers.0.role', 2));
});

test('candidates only see their own profile', function () {
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('profile.id', $candidate->id)
            ->where('profile.status', 0)
            ->missing('stats')
            ->missing('recentUsers'));
});
