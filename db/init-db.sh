#!/usr/bin/env bash
set -euo pipefail

DB_CONTAINER="skill-vault-mcp-db"
DB_USER="${POSTGRES_USER:-skill_vault}"
DB_NAME="${POSTGRES_DB:-skill_vault}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="$SCRIPT_DIR/schema.sql"

docker exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d postgres -c "DROP DATABASE IF EXISTS \"$DB_NAME\";"
docker exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d postgres -c "CREATE DATABASE \"$DB_NAME\";"
docker exec -i "$DB_CONTAINER" psql -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB_NAME" < "$SCHEMA_FILE"

echo "Database '$DB_NAME' (re)initialized from schema.sql"
