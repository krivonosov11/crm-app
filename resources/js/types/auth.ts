/** 0 = admin, 1 = HR, 2 = candidate. */
export type UserRole = 0 | 1 | 2;

/** 0 = created, 1 = processing, 2 = approved, 3 = rejected. */
export type CandidateStatus = 0 | 1 | 2 | 3;

export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    status?: CandidateStatus | null;
    has_cv?: boolean;
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type CandidateProfile = {
    summary: string | null;
    cv_name: string | null;
    has_cv: boolean;
    updated_at: string | null;
};

export type Auth = {
    user: User | null;
};
