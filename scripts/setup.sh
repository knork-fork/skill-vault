#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

set_env_var() {
    local env_file="$1"
    local key="$2"
    local value="$3"

    touch "$env_file"

    if grep -q "^${key}=" "$env_file" 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$env_file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$env_file"
    fi
}

SHARED_SECRET="$(openssl rand -hex 32)"

set_env_var "$REPO_ROOT/skill-vault-ui/.env.local" "OAUTH_INTROSPECTION_SHARED_SECRET" "$SHARED_SECRET"
set_env_var "$REPO_ROOT/skill-vault-mcp-server/.env.local" "OAUTH_INTROSPECTION_SHARED_SECRET" "$SHARED_SECRET"

echo "Generated a shared OAuth introspection secret and wrote it to:"
echo "  skill-vault-ui/.env.local"
echo "  skill-vault-mcp-server/.env.local"
