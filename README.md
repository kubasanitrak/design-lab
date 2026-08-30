# design-lab

WordPress plugin for Sobotní Design Lab (14 Saturday workshops, Design Lab pass).

Repository: https://github.com/kubasanitrak/design-lab

Default language of strings in code is **Czech**. English WordPress installs load `languages/design-lab-en_US.mo`.

## Shortcodes (Phase 1)

| Shortcode | Usage |
|-----------|--------|
| `[dlab_workshops_grid]` | Homepage / landing picks — `ids="1,2,3"`, `limit="6"`, `title="…"` |
| `[dlab_workshops_list]` | Full grid + filters — `show_filters="true"`, `filter_action="/design-lab/"` |
| `[dlab_workshop_detail]` | Detail (on singular omit `id`) — `id="123"` |
| `[dlab_add_to_pass]` | CTA only — `id="123"` (basket wiring in Phase 2) |
| `[dlab_basket_count]` | Header widget (returns `0` until Phase 2) |

**URL filters** (GET): `dlab_vek`, `dlab_obor` (term slugs).

Example listing page (created on activation):

```
[dlab_workshops_list show_filters="true" filter_action="/design-lab/"]
```

Example homepage picks:

```
[dlab_workshops_grid ids="12,15,18" limit="3" title="Sobotní Design Lab"]
```

CPT singles live at `/design-lab/{slug}/`. The archive is off so `/design-lab/` stays a real page.

Lektorky use the theme CPT `instructor` (relationship field). Do not create a second instructor type.

Pass rules (used from Phase 2): **2+ workshops**, **one attendee headcount for the whole pass**.

## Composer

Runtime dependencies are installed into `vendor/` and bundled in release zips (sites do not run Composer). Phase 1 has none.

```bash
composer install
```

The main plugin file loads `vendor/autoload.php` when present.

## Releasing an update

1. Bump `Version` and `DLAB_VERSION` in `design-lab.php`.
2. Add a `## [x.y.z]` section to `CHANGELOG.md`.
3. Commit and push to `main`.
4. Create and push a matching tag (header `0.1.0` → tag `v0.1.0`):

```bash
git tag v0.1.0
git push origin v0.1.0
```

GitHub Actions builds `design-lab.zip` and publishes a GitHub Release. Installed sites check for updates via [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (vendored under `lib/plugin-update-checker/`).

## Compile public CSS

```bash
npx --yes sass public/scss/public.scss public/css/public.css --no-source-map
```
