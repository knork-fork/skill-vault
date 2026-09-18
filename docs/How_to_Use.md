# How to Connect Claude to Skill Vault

This covers connecting Claude to an already-running Skill Vault. If you need to deploy or configure Skill Vault itself first, see [How to Setup](How_to_Setup).

Skill Vault exposes your enabled skills and tools to Claude over MCP (Model Context Protocol). Depending on where you use Claude, you can connect it in one of two ways: registering it as an MCP server in Claude Code, or adding it as a connector in Claude.ai (web).

Your Skill Vault MCP server URL is:

```
{{SKILL_VAULT_MCP_URL}}/mcp
```

## Enable some skills first

Before connecting, go to the **Skill Groups** tabs in Skill Vault and enable at least one skill or group. Nothing is exposed to Claude until you do — connecting with nothing enabled just gets you an empty toolset.

## Registering the MCP server in Claude Code

Claude Code can talk to the Skill Vault MCP server directly from the CLI.

1. From a terminal, register the server:

   ```
   claude mcp add --transport http skill-vault {{SKILL_VAULT_MCP_URL}}/mcp
   ```

   - `skill-vault` is the name Claude Code will use to refer to this server; you can pick any name.
   - `--transport http` tells Claude Code to talk to the server over HTTP rather than launching a local process.
   - add `-s user` to make it available in all your projects (`-s local` is default, which scopes mcp server to just your current folder)
2. After starting a new Claude Code session, Claude will display a message like:

   > 1 MCP server needs authentication · run /mcp

   Type in `/mcp` and select the `skill-vault` (or whichever name you chose) and choose "Authenticate".
   This will open a browser window to the Skill Vault login page.
   Sign in with your account and approve the request to authorize Claude Code.

3. You can start using Claude Code and it will automatically pick up enabled skills and use them based on what you ask, without needing to name them directly.

To remove the server later, run `claude mcp remove skill-vault`.

## Adding a connector in Claude.ai (web)

This is a two-step process: your organization owner adds the connector once, then each user connects their own account to it.

**Organization owner, one-time setup:**

1. Go to **Settings → Connectors** in Claude.ai.
2. Click **Add custom connector**.
3. Enter:
   - **Name**: `Skill Vault` (or any label you'll recognize)
   - **URL**: `{{SKILL_VAULT_MCP_URL}}/mcp` (deployment's public URL — Claude.ai must be able to reach it over the network)
4. Click **Add**.

**Each user:**

1. Go to [claude.ai/customize/connectors/directory](https://claude.ai/customize/connectors/directory).
2. Under **Custom connectors**, click **Connect Skill Vault to Claude**.
3. You'll be redirected to Skill Vault's allow page — sign in and approve the request. This is what lets Skill Vault know who you are, so it only exposes the skills and tools you personally have access to and have enabled.
4. Once connected, enable the connector in a conversation from the tools/connectors menu. Claude will then be able to use your Skill Vault skills and tools in that chat.

## Notes

- Skill Vault always enforces access on the server side: what you see and can run through Claude reflects only the skills, tools, and groups you currently have enabled and permission for — never more.
- Changes you make to which skills or groups are enabled apply the next time you start a conversation; they won't change tools already listed in a conversation that's already open.
- If a skill or tool call fails with a permission error, check the **Skills** and **Skill Groups** tabs to confirm it's still enabled and shared with you.
