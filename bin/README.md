# Admin/CLI tools (shell/command line only)

All admin/maintenance tools live in `bin/` and use a consistent shell entrypoint. They are **not** used by the browser UI.

## Unified command

```bash
bin/basicweb <command> [args]
```

Commands:
- `rebuild [--migrate] [--regenerate-html] [--cache] [--all]` — rebuild manifest/index and derived artifacts.
- `migrate` — migrate legacy flat content into bundles + rebuild index/cache.
- `git-sync [message]` — commit repository/content changes via git (CLI only; requires `git`).
- `test [--url=BASE_URL]` — run basic deployment tests (filesystem, PHP, config/functions, optional HTTP checks).

## Implementation scripts (called by the unified command)
- `bin/rebuild.php` — manifest rebuild/migration/HTML regeneration/cache (PHP CLI).
- `bin/git-sync.sh` — git commit helper.
- `bin/test-deploy.sh` — deployment test suite.

## Notes
- Run `bin/basicweb rebuild --all` after bulk/manual file changes or after deployment.
- Workflow/audit log: `content/_workflow.jsonl` (append-only JSON lines).
- Manifest/index: `content/_index.json` (derived; rebuilt/updated via CLI/per-article logic).
- Article bundles: `content/<fullId>/{article.md,meta.json,article.html,revisions/}`. Media uploads: `media/<fullId>/`. Trash: `content/_trash/`.

