# How to Set Up Skill Vault

This covers deploying and configuring a Skill Vault instance. If you just want to connect Claude to an already-running Skill Vault, see [How to Use](How_to_Use) instead.

## Running the services

```
docker-compose up -d --build
skill-vault-ui/docker/composer install
skill-vault-mcp-server/docker/composer install
./scripts/setup.sh
```

`scripts/setup.sh` generates the shared secret that `skill-vault-ui` and `skill-vault-mcp-server` use to validate each other's OAuth tokens. It's safe to re-run.

## Invite-only signup

By default, `/signup` on `skill-vault-ui` is open to anyone. To restrict it:

```
./scripts/enable_invite_only.sh
```

This writes a secret to `skill-vault-ui/.env.local` and prints an invite link (`/signup?invite=<secret>`).
Signup requires that link; re-running the script rotates the secret and invalidates the old one.

To disable signup entirely, set `INVITE_SECRET=disabled` in `.env.local`.
