# CLI tools (shell/command line only)

These scripts are intended to be run from the command line on the server (e.g. Synology SSH). They are **not** used by the browser UI.

- `php bin/rebuild.php [--migrate] [--regenerate-html] [--cache] [--all]`
  - Migrates legacy flat `content/*.md|html|meta.json` files into directory-per-article bundles (`content/<fullId>/`).
  - Rebuilds `content/_index.json` (manifest/index) used for fast listings/queues.
  - Optionally regenerates published `article.html` from Markdown.
  - Writes `cache/published.json`.
  - Appends a `rebuild` event to `content/_workflow.jsonl`.
- `bash bin/git-sync.sh ["commit message"]`
  - Commits repository/content changes via git (CLI only). Requires a git repo and `git` installed.

## Notes
- The browser app updates the manifest per article; run `rebuild.php` after bulk/manual file changes or after deploying migrated content.
- Workflow/audit log: `content/_workflow.jsonl` (append-only JSON lines).
- Article bundles: `content/<fullId>/{article.md,meta.json,article.html,revisions/}`. Media uploads remain in `media/<fullId>/` for simple web serving. Trash: `content/_trash/`.
