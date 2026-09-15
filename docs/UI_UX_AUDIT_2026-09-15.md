# EduCore UI/UX Audit — 15 September 2026

## Executive verdict

EduCore is a professional, commercially credible school ERP with a stronger visual and interaction foundation than a typical independently developed school-management system. The product is not yet consistently best-in-class across every legacy web view and native workflow, but its architecture is now capable of reaching that level without another wholesale redesign.

**Current overall assessment: 8.2/10**

The next maturity step is not “make it prettier.” It is systematic convergence on the existing EduCore design system: fewer ad-hoc styles, stronger accessibility, clearer workflow feedback, consistent information density, and more user-configurable dashboards.

## Benchmark context

The audit used mature school ERP/SIS patterns as reference points, especially products that emphasize:

- role-specific dashboards and navigation;
- configurable dashboard content;
- real-time operational KPIs;
- mobile/web continuity;
- simple high-frequency teacher workflows;
- strong reporting and data-density ergonomics;
- branded multi-school experiences.

Comparable products reviewed include Classter and Fedena. EduCore already matches or exceeds many competitors in visual modernity and role-aware module breadth. The largest remaining gap is systematic UX refinement rather than feature count.

## Scope reviewed

### Web / Laravel

- Shared application shell and brand layer
- Sidebar/navigation architecture
- Role/module visibility model
- Responsive utilities
- Cards, buttons, forms, tables, tabs and alerts
- Tenant presentation
- Legacy inline-style compatibility strategy
- Accessibility and interaction states

### Native Android / Jetpack Compose

- `core/designsystem` architecture
- Theme, colour system and spacing tokens
- Button touch targets
- Cards, inputs and feedback components
- Adaptive layout
- Authorized shell and navigation
- Dashboard composition
- Offline/cached state presentation
- Role-aware quick actions
- Typography and accessibility semantics

## Scorecard

| Area | Score | Assessment |
|---|---:|---|
| Brand identity | 9.2/10 | Distinct navy/gold identity; strong cross-surface recognisability |
| Visual hierarchy | 8.4/10 | Strong shell and component hierarchy; some legacy density remains |
| Navigation | 8.7/10 | Role-aware, collapsible/adaptive, clear active states |
| Responsive/adaptive behaviour | 8.7/10 | Mature web breakpoints and native adaptive layout |
| Role-based UX | 9.1/10 | One of EduCore's strongest differentiators |
| Dashboard UX | 8.2/10 | Strong operational overview; limited end-user personalisation |
| Data-heavy screens | 7.7/10 | Functional, but legacy tables/forms still need systematic refinement |
| Design-system maturity | 8.5/10 | Strong foundations on both web and native; adoption is not yet universal |
| Feedback/offline/error states | 8.5/10 | Native system is particularly strong; web remains more heterogeneous |
| Accessibility | 7.8/10 before this pass | Focus, motion and native text scale required improvement |
| Cross-platform consistency | 8.1/10 | Branding is aligned; interaction vocabulary still needs convergence |
| Overall | **8.2/10** | Commercial-grade, approaching top-tier |

## Key strengths

### 1. Real design-system architecture

The native app has a dedicated `core/designsystem` module containing theme, spacing/elevation/size tokens, buttons, cards, inputs, lists, overlays, feedback components, adaptive layout and a design-system gallery. This is significantly more mature than feature-local Compose styling.

The web app also has a shared brand stylesheet plus common layout utilities, cards, forms, badges, tables, responsive wrappers and navigation primitives.

### 2. Strong role-aware UX

The web navigation is filtered by actual role/module permissions rather than simply presenting every module to every user. The native dashboard similarly filters visible modules and quick actions against the authenticated session.

This is a high-value UX decision because it reduces cognitive load and prevents non-academic users from navigating through irrelevant academic functions.

### 3. Mature responsive/adaptive behaviour

The web shell supports desktop sidebar collapse, mobile drawer behaviour, responsive grids, wrapped page actions and horizontally scrollable tables.

The native shell uses compact vs expanded navigation patterns and adapts the dashboard grid to window width rather than forcing a phone-only layout.

### 4. Good native operational feedback

The Android design system already provides loading, empty, error, warning and offline states. The dashboard distinguishes cached information from live data rather than silently showing stale results.

### 5. Strong product identity

EduCore no longer looks like a generic Bootstrap school template. The navy/gold palette, branded navigation and repeated component vocabulary provide a distinct commercial identity.

## Highest-priority weaknesses

### P0 — Accessibility and legibility inconsistency

Before this remediation pass, Android body/label styles were as small as 9–12sp. The web also had inconsistent keyboard-focus visibility because legacy inline styles coexist with the global brand layer.

**Action:** fixed in this audit pass.

### P0 — Legacy web style drift

The web application still contains defensive responsive CSS specifically intended to override legacy inline grids and widths. This indicates that some screens are not yet built exclusively from the shared component vocabulary.

**Required follow-up:** migrate high-use screens to shared `ec-*`/layout components and remove local style duplication incrementally.

### P1 — Data-table ergonomics

Administrative tables remain one of the biggest UX opportunities. Horizontal scrolling is correctly supported, but top-tier ERP tables should progressively add sticky identifiers/headers where appropriate, column priority on narrow screens, saved filters, clearer bulk-selection state, and consistent action menus.

### P1 — Dashboard personalisation

EduCore already personalises access by role, but mature competitors allow users or administrators to configure dashboard widgets/dashlets. EduCore should eventually support safe per-role dashboard ordering/visibility without allowing users to expose unauthorised data.

### P1 — Cross-platform component parity

Web and Android now share the same brand, but component naming and interaction contracts should converge further: the same semantic states, destructive-action confirmation rules, disabled/loading behaviour, filter patterns and empty-state wording should be recognisable on both surfaces.

### P1 — Dense workflow optimisation

Frequent workflows such as attendance, score entry, fees, payroll and communication should be optimised for the shortest safe task path. Visual polish must not add taps or vertical space unnecessarily.

## Remediation implemented in this pass

### Web — `master`

1. Added a global brand-consistent `:focus-visible` treatment.
2. Standardised minimum interactive heights for buttons, tabs and pagination, with larger mobile targets.
3. Added `prefers-reduced-motion` support globally.
4. Standardised disabled-control presentation.
5. Corrected the shared `--indigo-dark` and `--indigo-bg` aliases so interaction states remain visually meaningful rather than resolving to the same colour.
6. Kept active-navigation emphasis explicitly on the EduCore gold system.

### Android — `mobile-build`

1. Increased typography to a more legible Material-compatible scale while preserving `sp` font scaling.
2. Strengthened heading weights and hierarchy.
3. Preserved the existing 48dp accessible touch-target token.
4. Added polite live-region semantics to banners, loading states and error states so dynamic operational feedback is announced by assistive technologies.
5. Kept static skeletons rather than introducing expensive infinite shimmer animations, which is appropriate for lower-memory devices.

## Next implementation phases

### Phase A — Web legacy convergence

Refactor the highest-use web screens first:

1. Dashboard
2. Students/staff directories
3. Attendance
4. Score entry/results
5. Fees/finance
6. Payroll/payslip
7. Academic repository
8. Communication

For each screen:

- replace inline layout styles with shared classes;
- use one canonical page header;
- use one filter-card pattern;
- use one empty/loading/error pattern;
- use one table/action-menu pattern;
- verify keyboard-only operation;
- verify 360px phone layout and 200% browser zoom.

### Phase B — Native feature convergence

Audit feature screens for direct Material components that bypass EduCore components. Replace them where the design-system component provides the same function, especially buttons, banners, cards, inputs, dialogs and page headers.

### Phase C — Dashboard configurability

Add role-safe dashboard configuration:

- administrator-defined default widgets by role;
- optional user ordering/hiding of non-critical widgets;
- immutable mandatory compliance/alert widgets;
- server-stored preferences so web and mobile can converge later.

### Phase D — Accessibility QA gate

Add release criteria covering:

- TalkBack traversal on core mobile workflows;
- keyboard-only web navigation;
- visible focus states;
- 200% web zoom;
- Android large-font testing;
- contrast validation;
- descriptive control labels;
- reduced-motion behaviour;
- error identification that does not rely on colour alone.

## Definition of “top-tier” for EduCore

EduCore should be considered 9+/10 when:

- no high-use screen depends on one-off legacy layout rules;
- common interactions use canonical shared components on each platform;
- the highest-frequency school tasks can be completed with minimal navigation;
- all core workflows have explicit loading, empty, error, offline and success states;
- accessibility QA is part of release verification rather than occasional cleanup;
- dashboard information can be safely configured by role;
- web and Android feel like two clients of one product system rather than two independently styled applications.

## Product direction

Do **not** perform another wholesale visual redesign. Preserve the current EduCore identity and architecture. The highest return now comes from controlled standardisation, workflow reduction, accessibility, information-density refinement and cross-platform consistency.
