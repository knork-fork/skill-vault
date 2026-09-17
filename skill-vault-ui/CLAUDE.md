# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Symfony 8 (PHP 8.5) app serving the Skill Vault's web UI — currently just login/signup/auth. Runs via the
project-root `docker-compose.yml` as the `webserver-ui` (nginx, host port 20100) + `php-fpm-ui` containers.
No Symfony Flex: bundles are registered by hand in `config/bundles.php`, and `config/packages/*.yaml` is
hand-maintained (there's no recipe system to regenerate it).

## Commands

All PHP tooling runs inside the `skill-vault-ui-php-fpm` container via wrapper scripts in `docker/` (each just
does `docker exec ... <tool>`), so the containers must be up first (`docker compose up -d` from the repo root).

- `docker/composer <args>` — composer, e.g. `docker/composer require <package>`
- `docker exec -t skill-vault-ui-php-fpm php bin/console <args>` — Symfony console (no `docker/console` wrapper exists); e.g. `cache:clear`, `doctrine:schema:validate`, `doctrine:mapping:info`
- `docker/phpunit <args>` — run tests, e.g. `docker/phpunit --filter testName` or `docker/phpunit tests/Unit/SomeTest.php`
- `docker/phpstan analyse` — static analysis at level 9 (see `phpstan.neon`)
- `docker/php-cs-fixer` — auto-fixes style; `docker/quality-check` runs cs-fixer (dry-run), phpstan, and phpunit in sequence and is the thing to run before considering work done
- `docker/shell` — drop into a bash shell in the php-fpm container

## Database

Postgres runs as the `db` service in the root `docker-compose.yml` (host port 20102, container name
`skill-vault-mcp-db`). The schema is **not** managed by Doctrine migrations — it's owned by the shared
`db/schema.sql` at the repo root and loaded via `db/init-db.sh` (also at the repo root, which drops and
recreates the whole database, so only run it deliberately). `DATABASE_URL` in `.env` points at the `db`
container. Doctrine entities in `src/Entity/` map onto those hand-written tables (`users`, `skill_groups`) —
when changing a column, edit `schema.sql` and the entity mapping together; `doctrine:schema:update` is not
used to migrate.

## Resources (skills & tools on disk)

Skills and tools are files under the repo-root `resources/` directory (format in `docs/Skills_vs_Tools.md`),
mounted into the `php-fpm-ui` container at `/resources` (see `RESOURCES_DIR` in `.env` and
`app.resources_dir` in `config/services.yaml`). `src/Skill/SkillFileRepository.php` and
`src/Tool/ToolFileRepository.php` read (and, for skills, write) that directory directly — there's no
database table for skills/tools themselves, only for `skill_groups`.

## Auth architecture

- `src/Entity/User.php` maps to the shared `users` table (id, username, password, email, created_at,
  first_name, last_name). Password hashing is bcrypt, configured per-class in `config/packages/security.yaml`
  (`password_hashers`), not the default `auto` hasher.
- `config/packages/security.yaml` defines the `main` firewall: `form_login` (login_path/check_path both
  `/login`), `remember_me`, and `logout`. `access_control` gates `/` behind `ROLE_USER`; `/login` and
  `/signup` are `PUBLIC_ACCESS`. Anonymous access to `/` redirects to `/login` through the firewall entry
  point, not through a dedicated redirect controller.
- `src/Controller/SecurityController.php` renders login (`templates/security/login.html.twig`) and has a
  logout stub the firewall intercepts.
- `src/Controller/RegistrationController.php` handles `/signup`: builds `RegistrationFormType`
  (`src/Form/`), hashes the password, persists via Doctrine, then calls `Security::login()` to auto-login
  before redirecting to `app_home` — there's no separate "verify your account" step.
- `src/Controller/HomeController.php` is the protected `/` route.
- All page templates extend `templates/base.html.twig`, which holds the dark-theme CSS inline (no
  asset pipeline/webpack — plain `<style>` block, no separate CSS files).
