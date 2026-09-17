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

SECRET="$(openssl rand -hex 32)"

set_env_var "$REPO_ROOT/skill-vault-ui/.env.local" "INVITE_SECRET" "$SECRET"

echo "Invite-only signup enabled. Required secret written to skill-vault-ui/.env.local."
echo "Invite link: <skill-vault-ui base URL>/signup?invite=${SECRET}"
echo
echo "Re-run this script to rotate the secret, or set INVITE_SECRET=disabled in"
echo "skill-vault-ui/.env.local to turn signup off entirely."
