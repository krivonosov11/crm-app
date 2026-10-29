#!/usr/bin/env bash
# PostToolUse (Write|Edit): keep edited files formatted and generated code in sync.
#  - PHP files            -> Laravel Pint
#  - resources/js TS/TSX  -> Vite+ formatter (skips generated Wayfinder files and components/ui)
#  - routes / controllers -> regenerate Wayfinder TypeScript routes
#  - Pest test files      -> run that test file; failures are fed back to Claude (exit 2)

cd "$CLAUDE_PROJECT_DIR" || exit 0

file=$(jq -r '.tool_input.file_path // .tool_response.filePath // empty')
[ -z "$file" ] && exit 0
[ -f "$file" ] || exit 0

rel="${file#"$CLAUDE_PROJECT_DIR"/}"

case "$rel" in
    *.php)
        vendor/bin/pint --format agent "$rel" >/dev/null 2>&1
        ;;
    resources/js/actions/* | resources/js/routes/* | resources/js/wayfinder/* | resources/js/components/ui/*)
        ;;
    resources/js/*.ts | resources/js/*.tsx)
        npx --no-install vp fmt "$rel" >/dev/null 2>&1
        ;;
esac

case "$rel" in
    routes/*.php | app/Http/Controllers/*.php)
        php artisan wayfinder:generate --with-form --no-interaction >/dev/null 2>&1
        ;;
esac

case "$rel" in
    tests/*Test.php)
        output=$(php artisan test --compact "$rel" 2>&1)
        if [ $? -ne 0 ]; then
            echo "Tests in $rel are failing after this edit:" >&2
            echo "$output" | tail -c 4000 >&2
            exit 2
        fi
        ;;
esac

exit 0
