# UI/UX Improvement Plan — Global Institute SPA

## Current State: Functional but Flat
The SPA works but looks like a rough prototype. The reference designs show a polished, professional engineering academy site with glass morphism, blueprint patterns, micro-interactions, and proper visual hierarchy.

---

## Phase 1: Critical Fixes (Must Do)

### 1.1 Fix Route Matching Bug
- **File:** `js/app.js` — Router class
- **Issue:** `/courses/:slug` parameterized route never matches. The `resolve()` method does strict equality (`this.routes[route]`) but the hash is `/courses/solidworks-professional`, not `/courses/:slug`.
- **Fix:** Add regex pattern matching in Router. When hash starts with `/courses/`, extract slug and call course detail handler with it.

### 1.2 Add 404 Page
- **Issue:** Unrecognized routes silently fall to home page.
- **Fix:** Add a `Pages.notFound()` handler that shows "Page Not Found" with a link back to home.

### 1.3 Fix Course Detail Data
- **Issue:** Unknown slugs silently show SolidWorks data as fallback.
- **Fix:** Show "Course Not Found" message instead of wrong data.

### 1.4 Smooth Scroll-to-Top
- **File:** `js/app.js` — `scrollToTop()` function
- **Issue:** Uses `behavior: 'instant'` causing a hard jump.
- **Fix:** Change to `behavior: 'smooth'`.

### 1.5 Add Page Transition Animation
- **Issue:** Content swaps are instant and jarring.
- **Fix:** Add a fade-in transition. Before replacing innerHTML, add a CSS class that fades the content out, then after a short delay (150ms), replace content and fade in.

---

## Phase 2: Visual Polish (High Impact)

### 2.1 Hero Section Redesign
- **Current:** Basic dark bg with text, a couple buttons.
- **Target:** Blueprint grid pattern background with `mix-blend-overlay` image, glass morphism pill badge with animated green dot, two-column layout with decorative floating card on desktop, hover lift effects on buttons.

**Changes to `app.js` home page:**
- Add `blueprint-dark` class to hero background
- Add glass-morphism badge pill (backdrop-blur, border-white/20)
- Add animated green pulse dot
- Add decorative rotated rectangle behind hero image
- Button hover: `brightness(1.05) shadow-lg -translate-y-0.5`

### 2.2 Course Cards Redesign
- **Current:** Plain cards with image, title, buttons.
- **Target:** Left accent borders (colored per category), `mix-blend-multiply` tinted images, skill tag pills, hover shadow + image zoom, `group` hover pattern.

**Changes to `app.js` `_courseCard()` helper:**
- Add left border accent: `border-l-4` with color based on category
- Image area: add `technical-grid` overlay, apply `mix-blend-multiply opacity-80`
- Add skill tag chips below description
- Add `group` class to card, image gets `group-hover:scale-105 transition-transform duration-500`
- Card gets `hover:shadow-card transition-shadow`

### 2.3 Gallery — Bento Grid
- **Current:** Simple 4-item CSS grid.
- **Target:** Asymmetric bento grid with gradient overlays, hover zoom, category badges.

**Changes to `app.js` gallery page:**
- Switch to `grid-cols-1 md:grid-cols-4 md:grid-rows-2` with `h-auto md:h-[600px]`
- Large feature cell: 2 cols × 2 rows
- Standard cells: 1×1
- Wide cell: 2 cols × 1 row
- Each cell: `bg-cover bg-center` with `bg-gradient-to-t from-primary/80 to-transparent` overlay
- Hover: `group-hover:scale-105 transition-transform duration-700`
- Category badges at bottom of each cell

### 2.4 Contact Section Polish
- **Current:** Basic two-column with form.
- **Target:** Decorative corner element, icon boxes for contact items, green left accent on form card, two-column form grid (name + phone side by side).

### 2.5 Section Title Pattern
- **Current:** Just plain text headings.
- **Target:** Green left border accent (`border-l-2 border-secondary`), uppercase JetBrains Mono label above, headline below. Consistent across all sections.

### 2.6 Testimonial Cards
- **Current:** Basic cards.
- **Target:** Star ratings with proper filled/empty stars, avatar initials circle, italic quote text, subtle card hover.

---

## Phase 3: Micro-Interactions (Polish)

### 3.1 Button Hover Effects
- All primary buttons: `hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200`
- All secondary/outline buttons: `hover:bg-surface-container-low transition-colors`
- FAB (mobile Enquire): `active:scale-90` press feedback

### 3.2 Card Hover Effects
- All cards: `hover:shadow-card hover:-translate-y-0.5 transition-all duration-300`
- Gallery cells: image zoom on hover via `group` pattern

### 3.3 FAQ Accordion Animation
- **Current:** Basic max-height toggle.
- **Target:** Smooth max-height with overflow hidden, rotate the chevron icon, slight background change on active item.

### 3.4 Nav Link Active State
- **Current:** Just text color change.
- **Target:** Bottom border underline animation on active link, smooth transition.

### 3.5 Loading Skeleton (Optional)
- Before content renders, show a brief skeleton loader (200ms) for perceived performance.

---

## Phase 4: Component Improvements

### 4.1 Footer Enhancement
- Add location line with pin icon
- Add social media icons with hover effects (rounded square, border, fill on hover)
- Footer links hover to green
- Better column spacing

### 4.2 Mobile Bottom Nav Polish
- WhatsApp icon in green color
- Enquire FAB with green background and press animation
- Proper z-index layering

### 4.3 Announcement Bar
- Add close button (localStorage to remember dismissal)
- Pulsing icon animation

### 4.4 Enquiry Modal
- Backdrop blur
- Smooth open/close animation
- Better form layout with two-column grid for name + phone

---

## Phase 5: Content Quality

### 5.1 Course Detail Page
- Fix routing so all 6 courses are accessible
- Add proper course data for NX CAD and Industrial Automation
- Stats grid in hero with accent borders
- Sticky sidebar with fee card + enquiry form
- Curriculum highlights with colored accent bars
- Career pathways with tag chips

### 5.2 About Page
- Better trainer cards with role descriptions
- Infrastructure cards with proper icons and descriptions
- Mission/Vision with better visual treatment

### 5.3 Admissions Page
- Eligibility card with icon list
- Process steps with numbered cards
- counselling form with proper validation

---

## Implementation Order

1. **Router fix** (1.1) — critical, blocks course detail
2. **Page transitions** (1.5) — biggest visual impact
3. **Smooth scroll** (1.4) — quick win
4. **404 page** (1.2) — quick win
5. **Hero redesign** (2.1) — first thing users see
6. **Course cards** (2.2) — most viewed component
7. **Section title pattern** (2.5) — consistency across all pages
8. **Gallery bento grid** (2.3) — visual impact
9. **Button hover effects** (3.1) — polish
10. **Card hover effects** (3.2) — polish
11. **Contact section** (2.4) — conversion page
12. **Footer** (4.1) — appears on every page
13. **Testimonials** (2.6) — social proof
14. **FAQ animation** (3.3) — engagement
15. **Course detail fix** (5.1) — conversion page
16. **About page** (5.2) — trust building
17. **Admissions** (5.3) — conversion

---

## Files to Modify

| File | Changes |
|------|---------|
| `public/js/app.js` | Router fix, page transitions, all page HTML updates, smooth scroll, 404 page |
| `public/css/style.css` | Blueprint patterns, glass morphism, bento grid, hover effects, transitions, animation keyframes |

## Estimated Effort
- Phase 1 (Critical): ~1 hour
- Phase 2 (Visual): ~2 hours
- Phase 3 (Micro): ~30 min
- Phase 4 (Components): ~1 hour
- Phase 5 (Content): ~1 hour
- **Total: ~5-6 hours**
