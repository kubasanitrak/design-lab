# ACF field groups (plugin)

Field groups are versioned here and loaded via `DLab_ACF`:

| File | Post types |
|------|------------|
| `group_dlab_workshop.json` | `dlab_workshop` |

Title photo uses native **featured image**. Detail text uses **content**. Short grid summary uses **excerpt** (fallback: ACF `synopsis`).

Lektorky are a relationship to the theme CPT `instructor` (not a plugin CPT).

## Sync after install

1. Activate **ACF Pro**.
2. Open **Vlastní pole** — if groups show *Sync available*, run **Sync**.
3. Edits saved in admin are written back to this folder by default (`DLAB_ACF_SAVE_JSON` defaults to on).

Disable saving into the plugin:

```php
define('DLAB_ACF_SAVE_JSON', false);
```
