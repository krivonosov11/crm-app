<?php

namespace App\Enums;

enum CandidateStatus: int
{
    case Created = 0;
    case Processing = 1;
    case Approved = 2;
    case Rejected = 3;

    /**
     * Find a status by its case-insensitive name (e.g. "approved").
     */
    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->name, $name) === 0) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get the lowercase names of all statuses.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $case) => strtolower($case->name), self::cases());
    }

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Processing => 'Processing',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
