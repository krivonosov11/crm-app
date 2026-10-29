<?php

use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('roles are stored as integers', function () {
    $hr = User::factory()->hr()->create();

    $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'role' => 0]);
    $this->assertDatabaseHas('users', ['id' => $hr->id, 'role' => 1]);
});

test('new candidates default to the created status', function () {
    $candidate = User::factory()->candidate()->create();

    expect($candidate->status)->toBe(CandidateStatus::Created)
        ->and($this->admin->status)->toBeNull();
});

test('admin can view all users', function () {
    User::factory()->hr()->create();
    User::factory(2)->candidate()->create();

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->has('users.data', 4)
            ->where('can', ['create' => true, 'delete' => true, 'filterByRole' => true]));
});

test('admin can filter by the admin role', function () {
    User::factory()->hr()->create();

    $this->actingAs($this->admin)
        ->get(route('users.index', ['role' => 0]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $this->admin->id));
});

test('users can be searched and filtered by status', function () {
    User::factory()->candidate()->create(['name' => 'Jane Searchable']);
    User::factory()->withStatus(CandidateStatus::Rejected)->create(['name' => 'John Other']);

    $this->actingAs($this->admin)
        ->get(route('users.index', ['search' => 'Searchable']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Jane Searchable'));

    $this->actingAs($this->admin)
        ->get(route('users.index', ['status' => 3]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'John Other'));
});

test('statuses are stored as integers and the created status can be filtered', function () {
    $created = User::factory()->candidate()->create();
    User::factory()->withStatus(CandidateStatus::Approved)->create();

    $this->assertDatabaseHas('users', ['id' => $created->id, 'status' => 0]);

    $this->actingAs($this->admin)
        ->get(route('users.index', ['status' => 0]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $created->id));
});

test('admin can create a user of any role', function (int $role) {
    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'role' => (string) $role,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::from($role))
        ->and(Hash::check('password', $user->password))->toBeTrue();
})->with([
    'admin' => 0,
    'hr' => 1,
    'candidate' => 2,
]);

test('a created candidate gets the created status by default', function () {
    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'New Candidate',
        'email' => 'candidate@example.com',
        'role' => '2',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->where('email', 'candidate@example.com')->value('status'))
        ->toBe(CandidateStatus::Created);
});

test('creating a user validates input', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => '',
            'email' => 'taken@example.com',
            'role' => '9',
            'status' => '9',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])
        ->assertSessionHasErrors(['name', 'email', 'role', 'status', 'password']);
});

test('admin can update a user without changing the password', function () {
    $user = User::factory()->candidate()->create();
    $originalPassword = $user->password;

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->role)->toBe(UserRole::Hr)
        ->and($user->status)->toBeNull()
        ->and($user->password)->toBe($originalPassword);
});

test('admin can update a user password', function () {
    $user = User::factory()->candidate()->create();

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => '2',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect(route('users.index'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('admin can delete a user but not themselves', function () {
    $user = User::factory()->hr()->create();

    $this->actingAs($this->admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    $this->assertModelMissing($user);

    $this->actingAs($this->admin)
        ->delete(route('users.destroy', $this->admin))
        ->assertForbidden();
});

test('hr only sees candidates', function () {
    $hr = User::factory()->hr()->create();
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($hr)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $candidate->id)
            ->where('can', ['create' => false, 'delete' => false, 'filterByRole' => false]));

    $this->actingAs($hr)
        ->get(route('users.index', ['role' => 0]))
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
});

test('hr can edit a candidate and change their status', function () {
    $hr = User::factory()->hr()->create();
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($hr)
        ->get(route('users.edit', $candidate))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('canAssignRole', false));

    $this->actingAs($hr)
        ->put(route('users.update', $candidate), [
            'name' => 'Renamed',
            'email' => $candidate->email,
            'status' => '1',
        ])
        ->assertRedirect(route('users.index'));

    $candidate->refresh();

    expect($candidate->name)->toBe('Renamed')
        ->and($candidate->status)->toBe(CandidateStatus::Processing);
});

test('hr cannot change a candidate role', function () {
    $hr = User::factory()->hr()->create();
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($hr)->put(route('users.update', $candidate), [
        'name' => $candidate->name,
        'email' => $candidate->email,
        'role' => '0',
        'status' => '2',
    ]);

    expect($candidate->refresh()->role)->toBe(UserRole::Candidate);
});

test('hr cannot create, delete, or edit non-candidates', function () {
    $hr = User::factory()->hr()->create();
    $otherHr = User::factory()->hr()->create();
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($hr);

    $this->get(route('users.create'))->assertForbidden();
    $this->post(route('users.store'), [])->assertForbidden();
    $this->delete(route('users.destroy', $candidate))->assertForbidden();
    $this->get(route('users.edit', $otherHr))->assertForbidden();
    $this->put(route('users.update', $this->admin), [])->assertForbidden();
});

test('candidates cannot manage users', function () {
    $candidate = User::factory()->candidate()->create();
    $other = User::factory()->candidate()->create();

    $this->actingAs($candidate);

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('users.create'))->assertForbidden();
    $this->post(route('users.store'), [])->assertForbidden();
    $this->get(route('users.edit', $other))->assertForbidden();
    $this->put(route('users.update', $other), [])->assertForbidden();
    $this->delete(route('users.destroy', $other))->assertForbidden();
});
