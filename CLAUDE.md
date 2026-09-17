This project is a Skill Vault for creating, organizing, versioning and managing access to shared and personal AI skills through a web UI. Users can choose which skills or skill groups are exposed to Claude through MCP, either directly or through a lightweight router, and optionally connect services such as Trello and Google Drive so skills can work with external data using each user’s own access.

## Repository structure

This repo holds two **separate, independently deployed services** — not a frontend/backend split of one
app. Each is its own full-stack PHP/Symfony application (its own `src/`, `public/`, config, tests, containers)
with its own persistence concerns. They only share the same Postgres database (see `resources/`) and
`db/schema.sql`; they do not share code.

- `resources/` - gitignored files for storing user data, skills, tools and other resources. This is where the
  Skill Vault stores its data.
- `db/` - shared Postgres schema and tooling (`schema.sql`, `init-db.sh`, `psql`) used by both services'
  php-fpm containers via the common `skill-vault-mcp-db` database — even though today only `skill-vault-ui`'s
  php-fpm actually persists application data (`users`, `skill_groups`) there. The schema is hand-written, not
  managed by Doctrine migrations; `init-db.sh` drops and recreates the whole database, so only run it
  deliberately.
- `skill-vault-mcp-server/` - the MCP server: serves and handles MCP requests, lists tools and skills, manages
  user auth, executes skills and tools. Has no UI of its own.
- `skill-vault-ui/` - the web UI application (Symfony, server-rendered Twig templates, no separate JS
  frontend) for managing skills, skill groups, and user access. See `skill-vault-ui/CLAUDE.md` for details on
  this service specifically.

Critical: never explore unrelated directories/services when prompted to specifically work with a target
directory. In particular, do not assume `skill-vault-ui` is "the frontend" and `skill-vault-mcp-server` is
"the backend" for it — treat them as two separate targets and ask if it's unclear which one (or both) a task
concerns.

## Definition of done

This list is provided as context on what the project is intended to accomplish. It is not a strict checklist, but rather a set of goals to guide development.

* Web UI for managing available skills and skill groups.
* Individual user authentication.
* Editable skills with version control.
* Detailed log of who made changes.
* Different levels of permissions:
  * read permission
  * write permission
* Approval process where a change has to be accepted by another person; otherwise it is flagged as unapproved, unless made by an admin or moderator within their approval scope.
* Admin has read and write access with no approval needed for everything.
* Moderator has read and write access with no approval needed inside their own group.
* Moderators/admins can choose which of their skills are public with read-only access to all other users.
* When a user has read-only access, they can still:
  * suggest edits
  * make a copy of the skill and make their own edits to it
* Users can add their own individual skills.
* Users can tag their own skills with different labels.
* Moderators/admins can incorporate individual user skills into a wider category/group.
* Edge case: if a user enabled a skill, a moderator later incorporated it into a group and decided not to expose it as a public skill, the user loses access to that skill.
* UI allows users to:
  * pick skill groups
  * enable/disable individual skills within groups
  * pick and choose between ungrouped skills
* Users can connect their own Trello and Google Drive accounts via OAuth.
* One company-level MCP connector can be added to Claude web.
* MCP requests preserve individual user identity.
* Skill Vault exposes only skills/tools the authenticated user currently has access to and has enabled.
* Trello/Google Drive access uses that user’s own OAuth credentials and permissions.
* Skills can contain company-specific instructions, workflows, and knowledge.
* Skills can orchestrate reusable MCP tools.
* MCP tools can provide capabilities such as reading Trello cards, comments, attachments, and Google Drive files.
* Users can choose between:
  * **Direct exposure:** enabled skills/tools are exposed individually to Claude.
  * **Routed exposure:** only a small `gc-vault`-style router is exposed initially.
* In routed mode, the router calls home to fetch the authenticated user’s skill index, with unselected groups and inaccessible skills filtered out.
* Routed index contains only lightweight metadata needed to select a skill.
* Full skill instructions are fetched only when that skill is selected.
* Access restrictions are enforced server-side for both discovery and execution.
* Permissions are revalidated when a tool/skill is executed.
* Changes to enabled skills/groups only need to take effect in new Claude conversations.
* Users can invoke enabled functionality from normal Claude prompts without manually naming MCP tools.
