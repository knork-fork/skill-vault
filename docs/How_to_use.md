# How to Connect Claude to Skill Vault

Skill Vault exposes your enabled skills and tools to Claude over MCP (Model Context Protocol). Depending on where you use Claude, you can connect it in one of two ways: registering it as an MCP server in Claude Code, or adding it as a connector in Claude.ai (web).

Your Skill Vault MCP server URL is:

```
http://localhost:20101/mcp
```

Replace `localhost:20101` with your Skill Vault deployment's actual host if it isn't running locally.

## Registering the MCP server in Claude Code

Claude Code can talk to the Skill Vault MCP server directly from the CLI.

1. Make sure the Skill Vault MCP server is running and reachable (for a local Docker setup, `docker compose up -d` from the repo root), and that `./scripts/setup.sh` has been run at least once — it generates the shared secret the two services use to validate each other's OAuth tokens. Without it, the sign-in step below will fail.
2. From a terminal, register the server:

   ```
   claude mcp add --transport http skill-vault http://localhost:20101/mcp
   ```

   - `skill-vault` is the name Claude Code will use to refer to this server; you can pick any name.
   - `--transport http` tells Claude Code to talk to the server over HTTP rather than launching a local process.
   - use `-s user` instead of the default `-s local` to make it available in all your projects, not just this folder.
3. If the server requires you to sign in, Claude Code will prompt you to authenticate the first time it connects. Approve the request in the browser window that opens.
4. Verify the connection:

   ```
   claude mcp list
   ```

   `skill-vault` should show up with a connected status.
5. Start (or restart) a Claude Code session. Your enabled skills and tools are now available — Claude will pick them up automatically based on what you ask, without needing to name them directly.

To remove the server later, run `claude mcp remove skill-vault`.

## Adding a connector in Claude.ai (web)
(**WIP, not yet released**)

Claude.ai lets you add the same MCP server as a custom connector so it's available in your web conversations.

1. Go to **Settings → Connectors** in Claude.ai.
2. Click **Add custom connector**.
3. Enter:
   - **Name**: `Skill Vault` (or any label you'll recognize)
   - **URL**: `http://localhost:20101/mcp` (use your deployment's public URL — Claude.ai must be able to reach it over the network, so a `localhost` address only works if the server is exposed publicly or through a tunnel)
4. Click **Add**, then follow any sign-in prompt to authorize your account. This is what lets Skill Vault know who you are, so it only exposes the skills and tools you personally have access to and have enabled.
5. Once connected, enable the connector in a conversation from the tools/connectors menu. Claude will then be able to use your Skill Vault skills and tools in that chat.

Claude.ai only supports one company-level connector at a time, so coordinate with your admin if a Skill Vault connector is already set up for your organization.

## Notes

- Skill Vault always enforces access on the server side: what you see and can run through Claude reflects only the skills, tools, and groups you currently have enabled and permission for — never more.
- Changes you make to which skills or groups are enabled apply the next time you start a conversation; they won't change tools already listed in a conversation that's already open.
- If a skill or tool call fails with a permission error, check the **Skills** and **Skill Groups** tabs to confirm it's still enabled and shared with you.
