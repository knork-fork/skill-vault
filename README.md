# skill-vault-mcp

A Skill Vault for creating, organizing, versioning and permissioning shared or personal AI skills through a web UI. Users choose which skills and groups they want exposed to Claude through MCP, either directly or through a lightweight router, while optional per-user integrations such as Trello and Google Drive let those skills work with external data using each user’s own access.

## Setup

```
docker-compose up -d --build
./scripts/setup.sh
```

`scripts/setup.sh` generates the shared secret that `skill-vault-ui` and `skill-vault-mcp-server` use to validate
each other's OAuth tokens (see `docs/How_to_use.md`). It's safe to re-run.

## Invite-only signup

By default, `/signup` on `skill-vault-ui` is open to anyone. To restrict it:

```
./scripts/enable_invite_only.sh
```

This writes a secret to `skill-vault-ui/.env.local` and prints an invite link (`/signup?invite=<secret>`).
Signup requires that link; re-running the script rotates the secret and invalidates the old one.

To disable signup entirely, set `INVITE_SECRET=disabled` in `.env.local`.