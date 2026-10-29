<?php

namespace Database\Seeders;

use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Administrator', 'email' => 'admin@example.com', 'role' => UserRole::Admin],
            ['name' => 'HR Manager', 'email' => 'hr@example.com', 'role' => UserRole::Hr],
            ['name' => 'Test Candidate', 'email' => 'candidate@example.com', 'role' => UserRole::Candidate],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [...$account, 'password' => 'password', 'email_verified_at' => now()],
            );
        }

        foreach (CandidateStatus::cases() as $status) {
            User::factory(3)
                ->withStatus($status)
                ->has(CandidateProfile::factory(), 'candidateProfile')
                ->create();
        }
    }
}
