# skill-vault-mcp

A Skill Vault for creating, organizing, versioning and permissioning shared or personal AI skills through a web UI. Users choose which skills and groups they want exposed to Claude through MCP, either directly or through a lightweight router, while optional per-user integrations such as Trello and Google Drive let those skills work with external data using each user’s own access.

## Setup

```
docker-compose up -d --build
./scripts/setup.sh
```

`scripts/setup.sh` generates the shared secret that `skill-vault-ui` and `skill-vault-mcp-server` use to validate
each other's OAuth tokens (see `docs/How_to_use.md`). It's safe to re-run.