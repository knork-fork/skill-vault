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

## Backend endpoint

`/mcp` - receives tool-calling request with a body like:
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

Based on name=="trello_get_card", backend forwards the request to the appropriate backend service class.