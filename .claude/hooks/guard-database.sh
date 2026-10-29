#!/usr/bin/env bash
# PreToolUse (Bash): always ask before commands that wipe or roll back the local MySQL database.

command=$(jq -r '.tool_input.command // empty')

if echo "$command" | grep -Eq 'artisan[[:space:]]+(migrate:(fresh|refresh|reset|rollback)|db:wipe)'; then
    jq -n '{
        hookSpecificOutput: {
            hookEventName: "PreToolUse",
            permissionDecision: "ask",
            permissionDecisionReason: "This command drops or rolls back tables in the local MySQL database (DB_CONNECTION=mysql). Prefer a new migration that converts data in place unless a full reset is wanted."
        }
    }'
fi

exit 0
