# Loading & Skeleton System

One vocabulary for every surface: the Blade admin, the admin SPA, the Online
Learning SPA and the Live Class Room.

- **Styles** — `resources/css/admin.css`, section *Loading & Skeletons*
- **Utilities** — `resources/js/ui/loading.js` (also `window.Loading`)
- **Auto-wiring** — `resources/js/ui/blade-loading.js`
- **Blade components** — `resources/views/components/skeleton/*`, `components/loader/*`

---

## Which loader for what

| Situation | Use | Why |
|---|---|---|
| First load of a list, table, card grid, form | **Skeleton** | Shows the shape of what is coming |
| Refresh of a section (search, filter, sort, page) | **Section loader** | Keeps the old rows visible and dims them |
| A button that saves, deletes, uploads, joins | **Button loader** | Feedback where the click happened, and blocks the second click |
| Any navigation or fetch | **Page progress bar** | Non-blocking; the page stays usable |
| Genuinely blocking, app-level work | **Page overlay** | Rare by design — `showPageOverlay()` |

Full-screen loaders are deliberately hard to reach: the top progress bar covers
navigation and fetches, so the overlay is reserved for the few operations where
the user really must wait.

---

## JavaScript API

```js
import { showSkeleton, hideSkeleton, setButtonLoading } from '@/ui/loading.js';
// or, in Blade / plain scripts: window.Loading.showSkeleton(...)
```

| Function | Notes |
|---|---|
| `showSkeleton(target, type, opts)` | Stashes the current markup, sets `aria-busy` |
| `hideSkeleton(target, html?)` | Swaps in `html`, or restores what was there; fades via `.content-enter` |
| `showLoader(target, label?)` | Overlays one section (`.section-loader`) |
| `hideLoader(target)` | Removes the overlay, content untouched |
| `setButtonLoading(btn, on, text?)` | Disables + spins; restores the original label |
| `isButtonLoading(btn)` | The duplicate-click check |
| `withButtonLoading(btn, fn, text?)` | Runs `fn` with the button busy; **returns immediately if already busy** |
| `showPageLoading()` / `hidePageLoading()` | Ref-counted top bar — overlapping requests are safe |
| `showPageOverlay(label)` / `hidePageOverlay()` | Blocking; use sparingly |
| `fetchWithProgress(input, init)` | `fetch` with the bar attached |

### Skeleton types

`text · stats · table · list · cards · form · chart · profile · calendar · chat ·
roster · dashboard · modal`

```js
showSkeleton('#list-body', 'table', { rows: 8, cols: 6 });
showSkeleton('#chat-log', 'chat', { rows: 5 });
```

Each mirrors the real layout's box model, so the swap does not shift the page.

---

## Blade

Components, so no template repeats skeleton markup:

```blade
<x-skeleton.table :rows="6" :cols="5" />
<x-skeleton.stats :count="4" />
<x-skeleton.cards :count="6" />
<x-skeleton.form :fields="8" />
<x-skeleton.dashboard />

<x-loader.spinner size="sm" />
<x-loader.section label="Loading…" />
<x-loader.button loading-text="Saving…">Save course</x-loader.button>
```

### Nothing to wire by hand

`blade-loading.js` is delegated from the document, so **existing pages already
have loading states**:

- form submit → submit button busy, progress bar, **second submit blocked**
- a form with `_method=DELETE` → "Deleting…"
- a form containing a file input → "Uploading…"
- `select.filter-select` change → section loader ("Filtering…")
- `input.search-input` typing → debounced section loader ("Searching…")
- pagination click → section loader + progress bar
- any internal link → progress bar
- axios request/response → progress bar

The section is resolved by convention: an explicit `data-loading-section`
ancestor wins, otherwise the `.card` wrapping the page's `.admin-table` — which
is how all 24 admin index pages are built. Opt out with `data-no-loading`.

A button can opt in declaratively:

```blade
<button data-loading-text="Publishing…">Publish</button>
```

---

## Theming

Skeletons read `--skeleton-bg`, so the same classes work on light and dark:

```html
<body data-skeleton-theme="dark">   <!-- learning SPA + class room -->
```

---

## Accessibility & motion

- A loading container gets `aria-busy="true"`; skeleton markup is wrapped in
  `aria-hidden="true"` so it is never announced or focusable.
- Busy buttons are genuinely `disabled`, not just styled.
- `@media (prefers-reduced-motion: reduce)` collapses the pulse, spin and fade.

---

## Where it is applied

| Surface | Loading behaviour |
|---|---|
| Blade admin (all portals, CMS, courses, batches, teachers, students, fees, payments, enquiries, reports) | Auto-wired: submits, filters, search, pagination, navigation |
| Admin SPA (`public/js/admin.js`) | Table/stats/form skeletons on first paint; section overlay on refresh; button loaders on save/delete; progress bar on every request |
| Online Learning SPA | Per-page skeletons via `components/LoadingState.js`; progress bar in `api/client.js` and the router |
| Live Class Room | Shell skeleton in Blade; per-panel skeletons matched to each panel; guarded Join / Start / End / Save-attendance; progress bar on user-initiated calls only (pollers stay silent) |

Polled endpoints pass `silent: true` so a 5-second chat poll never flashes the
bar.
