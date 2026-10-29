# CRM App

Sample CRM: HR staff manage candidates. Laravel 13 + Inertia v3 + React 19 + Tailwind 4 + Wayfinder, PHP 8.5, Pest. MCP servers will be added later (`laravel/mcp` is installed) and should reuse the existing models, policies and form requests.

Laravel Boost guidelines (framework rules, tool usage, conventions) are imported here and must be followed:

@AGENTS.md

Boost skills live in `.junie/skills/` (not `.claude/skills/`). Read the relevant `SKILL.md` before working in that area: `inertia-react-development`, `wayfinder-development`, `tailwindcss-development`, `laravel-best-practices`, `testing-best-practices`, `mcp-development`.

## Domain

- **Roles** are integers. `App\Enums\UserRole`: `0` Admin, `1` HR, `2` Candidate (`users.role`, tinyint).
- **Candidate status** is an integer. `App\Enums\CandidateStatus`: `0` Created, `1` Processing, `2` Approved, `3` Rejected (`users.status`, nullable).
    - `User::booted()` enforces it: non-candidates always get `null`, and candidates without a status get `Created`. Don't duplicate this in controllers.
- **Candidate profile**: `App\Models\CandidateProfile` (`User` hasOne `candidateProfile`) holds `summary` and the CV (`cv_path`, `cv_original_name`).
    - CVs are stored on the private `local` disk under `cvs/` and are only served through `CandidateCvController` (`users.cv`).
    - Deleting a profile, or its user, deletes the file.
- The frontend mirrors the enums in `resources/js/lib/users.ts` (`Role`, `Status`, label maps) and `resources/js/types/auth.ts`. Keep both in sync when an enum changes.

## Permissions

| Action                    | Admin                | HR                             | Candidate        |
| ------------------------- | -------------------- | ------------------------------ | ---------------- |
| List / view users         | all                  | candidates only                | none             |
| Create, delete users      | yes (not themselves) | no                             | no               |
| Edit users                | anyone, incl. role   | candidates only, role excluded | no               |
| Own summary + CV          | no                   | no                             | yes (`/profile`) |
| View summary, download CV | all                  | all candidates                 | own only         |

- Rules live in `app/Policies/UserPolicy.php` (`viewAny`, `view`, `create`, `update`, `assignRole`, `delete`) and `app/Policies/CandidateProfilePolicy.php` (`view`, `manageOwn`).
- Form requests authorize through these policies.
- `UpdateUserRequest` removes `role` from the validated data via `Rule::excludeIf` when the user can't `assignRole`.
- Pages get UI flags as a `can` prop from the controller; the backend never trusts the frontend.

## Gotchas

- **`0` is a valid enum value.** Admin and Created are both `0`, and PHP treats `0` as empty, so never filter with `->when($value, ...)`. Use `->when(filled($value), ...)` and cast with `(int)`. On the frontend, compare against `null`/`undefined`, not falsiness, and keep select values as strings.
- **The dev database is MySQL**, not SQLite. Tests use in-memory SQLite. Change schema with new migrations that convert existing data in place (see the `convert_*` migrations), not with `migrate:fresh`. A hook asks before destructive migrate commands.
- **Feature tests that render Inertia pages need a Vite manifest.** Run `npm run build` if tests fail with "Unable to locate file in Vite manifest".
- **Wayfinder output is generated and gitignored** (`resources/js/actions`, `routes`, `wayfinder`). After route or controller changes, run `php artisan wayfinder:generate --with-form`; the post-edit hook does this automatically. Import from `@/actions/...` or `@/routes/...`, never hardcode URLs.
- **Inertia flash messages**: use `Inertia::flash('success', ...)` on the server and read `usePage().flash.success` on the client (typed in `resources/js/types/global.d.ts`).
- **Persistent layout**: pages set `Page.layout = (page) => <AppLayout>{page}</AppLayout>`.
- **CV uploads are capped at 2 MB** (`UpdateCandidateProfileRequest::MAX_CV_KILOBYTES`) to match PHP's `upload_max_filesize`.

## Commands

```bash
php artisan test --compact [path|--filter=name]   # Pest
vendor/bin/pint --format agent                    # PHP formatting (no git, so --dirty does not work)
vendor/bin/phpstan analyse --memory-limit=1G      # Larastan; the default 128M crashes
npm run types:check                               # tsc
npm run check / npm run check:fix                 # Vite+ lint + format
npm run build                                     # needed before feature tests on a fresh checkout
php artisan migrate && php artisan db:seed        # seeder is safe to re-run for the fixed accounts
```

Seeded logins, all with password `password`:

- `admin@example.com`
- `hr@example.com`
- `candidate@example.com`
- 12 random candidates, 3 per status, each with a summary

## MCP servers (`routes/ai.php`, `app/Mcp`)

- **`candidates`** is a local stdio server (`Mcp::local`) built from `CandidatesServer` plus 4 tools:
    - `list-candidates`, `get-candidate`, `candidate-pipeline-stats` (read-only)
    - `update-candidate-status` (the only tool that writes)
- **Acting user:** stdio has no HTTP auth, so `CandidatesServer::boot()` signs in the account from `MCP_USER_EMAIL` (`config('services.mcp.acting_user_email')`).
- **Prompts** (`app/Mcp/Prompts`): `review-candidate`, `shortlist-candidates`, `draft-candidate-email` and `pipeline-report`. Each one embeds live CRM data as JSON in the prompt text and tells the model not to change statuses without confirmation.
- **Authorization:** tools and prompts share the `App\Mcp\Concerns\InteractsWithCandidates` trait. It checks `UserPolicy` and holds the shared serialization and `pipelineStats()`. With no user, or a candidate account, everything returns "Access denied".
- Tools take statuses as lowercase names (`CandidateStatus::names()` / `tryFromName()`), not integers. Give every new tool an explicit `#[Name]` and `#[Title]`, because the default name gets a `-tool` suffix.
- Test with `CandidatesServer::actingAs($user)->tool(...)` (see `tests/Feature/CandidatesMcpServerTest.php`). Don't run `mcp:start` by hand; it waits for input. Use `php artisan mcp:inspector candidates` instead.
- `WeatherServer` (`/mcp/weather`) is the starter-kit demo. It's public, so remove it before deploying.

## Claude Code hooks (`.claude/settings.json`)

- `.claude/hooks/post-edit.sh` runs after Write/Edit:
    - Pint on PHP files.
    - `vp fmt` on `resources/js` TS/TSX files (skips generated files and `components/ui`).
    - `wayfinder:generate` after route or controller edits.
    - Runs an edited `tests/**/*Test.php` file and reports failures back.
- `.claude/hooks/guard-database.sh` asks for confirmation before `migrate:fresh|refresh|reset|rollback` or `db:wipe`.

## Before finishing a change

1. Run the narrowest relevant tests, then ask the user to run the full suite.
2. Run Pint, PHPStan (`--memory-limit=1G`), `npm run types:check` and `npm run check`.
3. Don't add dependencies or new top-level folders without approval.
