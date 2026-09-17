This doc explains the difference between skills and tools in the context of MCP using a simple example, and should be taken as a reference for how to design skills and tools in the Skill Vault.

Items are mentioned in the order of appearance, from user query to execution.

## Input

What user types:

> Compile all notes from https://trello.com/c/abc123/foo

## Skill

`resources/skills/compile_trello_notes/skill.md`

```
# Compile Trello Notes

Fetch the Trello card using `trello_get_card`.

Then:
- read the description
- read comments chronologically
- identify decisions
- treat later explicit decisions as superseding earlier ones
- list unresolved questions
```

`resources/skills/compile_trello_notes/metadata.yaml`
```
name: compile_trello_notes
description: Compile a Trello card into current decisions, relevant history and unresolved questions.

requires:
  - trello_get_card
```

## (MCP) Tool

`resources/tools/trello_get_card/tool.yaml`

```
name: trello_get_card
description: Fetch a Trello card and its relevant data using the current user's Trello account.

inputSchema:
  type: object
  properties:
    card_url:
      type: string
  required:
    - card_url
```

## MCP exposure (direct mode)

In direct exposure mode, both skills and tools are exposed to Claude as **MCP tools** — there is no separate
MCP primitive for skills. This is because MCP prompts are user-invoked (a person has to pick one from a
menu), not model-invoked, so a skill exposed as a prompt is never picked up automatically from a normal
Claude request. Exposing a skill as a tool instead means the model can select and call it itself, the same
way it would call any other tool.

So `tools/list` returns both `compile_trello_notes` (the skill) and `trello_get_card` (the tool) as tools.
A skill's `inputSchema` takes no arguments — calling it just returns its instructions:

```
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "tools": [
      {
        "name": "compile_trello_notes",
        "description": "Compile a Trello card into current decisions, relevant history and unresolved questions.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "trello_get_card",
        "description": "Fetch a Trello card and its relevant data using the current user's Trello account.",
        "inputSchema": {
          "type": "object",
          "properties": { "card_url": { "type": "string" } },
          "required": ["card_url"]
        }
      }
    ]
  }
}
```

This also holds for low-level tools that are hidden from the human-facing UI and only exist as a
dependency of an enabled skill (`requires`) — they are still exposed as MCP tools, just not surfaced to the
user for individual selection.

## Backend endpoint

`/mcp` - receives tool-calling requests, dispatched by `params.name`:

```
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "trello_get_card",
    "arguments": {
      "card_url": "..."
    }
  }
}
```

- `name == "trello_get_card"` (a tool) → backend forwards the request to the appropriate backend service
  class, which executes the integration logic and returns the resulting data.
- `name == "compile_trello_notes"` (a skill) → backend returns the skill's instructions (the contents of
  `skill.md`) as the tool result, without executing anything.