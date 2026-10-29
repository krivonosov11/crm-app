<?php

namespace App\Enums;

enum UserRole: int
{
    case Admin = 0;
    case Hr = 1;
    case Candidate = 2;

    /**
     * Get the human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Hr => 'HR',
            self::Candidate => 'Candidate',
        };
    }
}
