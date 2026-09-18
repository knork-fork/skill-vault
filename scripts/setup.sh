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

read -r -p "Full URL of skill-vault-ui [http://localhost:20100]: " SKILL_VAULT_UI_URL
SKILL_VAULT_UI_URL="${SKILL_VAULT_UI_URL:-http://localhost:20100}"

read -r -p "Full URL of skill-vault-mcp-server [http://localhost:20101]: " SKILL_VAULT_MCP_URL
SKILL_VAULT_MCP_URL="${SKILL_VAULT_MCP_URL:-http://localhost:20101}"

set_env_var "$REPO_ROOT/skill-vault-mcp-server/.env.local" "SKILL_VAULT_UI_URL" "$SKILL_VAULT_UI_URL"
set_env_var "$REPO_ROOT/skill-vault-ui/.env.local" "SKILL_VAULT_MCP_URL" "$SKILL_VAULT_MCP_URL"

set_env_var "$REPO_ROOT/skill-vault-ui/.env.local" "APP_ENV" "prod"
set_env_var "$REPO_ROOT/skill-vault-ui/.env.local" "APP_DEBUG" "0"
set_env_var "$REPO_ROOT/skill-vault-mcp-server/.env.local" "APP_ENV" "prod"
set_env_var "$REPO_ROOT/skill-vault-mcp-server/.env.local" "APP_DEBUG" "0"

docker compose -f "$REPO_ROOT/docker-compose.yml" exec -T -u www-data php-fpm-ui sh -c '
    mkdir -p /resources/skills
    if [ ! -d /resources/skills/.git ]; then
        git init /resources/skills
    fi
'

SHARED_SECRET="$(openssl rand -hex 32)"

set_env_var "$REPO_ROOT/skill-vault-ui/.env.local" "OAUTH_INTROSPECTION_SHARED_SECRET" "$SHARED_SECRET"
set_env_var "$REPO_ROOT/skill-vault-mcp-server/.env.local" "OAUTH_INTROSPECTION_SHARED_SECRET" "$SHARED_SECRET"

echo "Generated a shared OAuth introspection secret and wrote it to:"
echo "  skill-vault-ui/.env.local"
echo "  skill-vault-mcp-server/.env.local"
