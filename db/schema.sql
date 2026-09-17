CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(180),
    created_at TIMESTAMP NOT NULL DEFAULT now(),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL
);

CREATE TABLE skill_groups (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) NOT NULL DEFAULT 'folder',
    color VARCHAR(50) NOT NULL DEFAULT 'blue',
    created_at TIMESTAMP NOT NULL DEFAULT now(),
    updated_at TIMESTAMP NOT NULL DEFAULT now()
);

-- Per-user enable/disable opt-ins. Skills have no table of their own (they're files under
-- resources/skills/<slug>, see skill-vault-ui/CLAUDE.md), so they're keyed by slug here; skill
-- groups are keyed by their skill_groups.id. See skill-vault-ui's SkillAccessService for how an
-- entry (or its absence) here is resolved into an effective enabled/disabled/mixed state.
CREATE TABLE user_skill_states (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_slug VARCHAR(255) NOT NULL,
    enabled BOOLEAN NOT NULL,
    UNIQUE (user_id, skill_slug)
);

CREATE TABLE user_skill_group_states (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_group_id BIGINT NOT NULL REFERENCES skill_groups(id) ON DELETE CASCADE,
    enabled BOOLEAN NOT NULL,
    UNIQUE (user_id, skill_group_id)
);

-- OAuth 2.0 authorization server tables (skill-vault-ui issues and owns these;
-- skill-vault-mcp-server never queries them directly, only via the introspection
-- endpoint). Public clients only (PKCE required, no client secret).
CREATE TABLE oauth_clients (
    client_id VARCHAR(64) PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    redirect_uris TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE oauth_auth_codes (
    id BIGSERIAL PRIMARY KEY,
    code_hash VARCHAR(64) NOT NULL UNIQUE,
    client_id VARCHAR(64) NOT NULL REFERENCES oauth_clients(client_id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    redirect_uri TEXT NOT NULL,
    code_challenge VARCHAR(128) NOT NULL,
    code_challenge_method VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE oauth_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    client_id VARCHAR(64) NOT NULL REFERENCES oauth_clients(client_id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE oauth_refresh_tokens (
    id BIGSERIAL PRIMARY KEY,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    client_id VARCHAR(64) NOT NULL REFERENCES oauth_clients(client_id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT now()
);
