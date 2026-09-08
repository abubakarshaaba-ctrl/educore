# EduCore Mobile Overhaul — Phase B: Information Architecture

## Goal

Replace the web-module-directory mental model with task-oriented mobile workspaces while preserving the existing EduCore brand palette: navy, white and gold, with green/red reserved for semantic success/danger states.

## Staff navigation

The first production target is the staff portal because the poor screens reported during the overhaul came from a Form & Subject Teacher account.

### Home

Purpose: daily working dashboard, not a module directory.

- identity and role
- current school / term
- compact overview metrics
- dashboard sections supplied by the server
- quick actions that route only to native workspaces
- no browser fallback

### Classes

Purpose: assigned teaching workspaces.

- direct list of assigned classes
- class search
- class workspace
- student list/profile
- student attendance
- score entry
- class schedule
- lesson/repository links from their own native workspaces

The Classes tab must not display staff attendance, payroll, report cards, CBT administration or unrelated operational modules.

### Timetable

Purpose: schedule workspace.

- weekly timetable
- published examinations
- assigned examination duties
- class-specific schedule when opened from a class workspace

### Inbox

Purpose: communication centre.

- notices
- messages
- events
- unread counts
- compose/reply flows

### More

Purpose: secondary tools only.

- staff attendance
- lesson planner
- academic repository
- subjects/curriculum/session tools where permitted
- reports/CBT/profile are visibly marked as native work still in progress until Phase C completes them
- no automatic browser opening

## Screen-density rules

- module cards use content-driven height with a compact minimum instead of the previous fixed 136dp height
- primary card surfaces are white
- icon accents are gold/navy
- internal module keys are never user-facing subtitles
- green is limited to success/present/active states
- red is limited to danger/error/absent/destructive states
- warning/pending states use gold
- informational states stay in the navy family

## Rollout policy

The new task-oriented shell is activated first for `portal == staff`.

Other portals continue through the existing shell until their native workflows are audited and migrated. This limits regression risk while Phase C completes role-specific native coverage.

## Exit criteria

Phase B is complete for staff when:

1. Home, Classes, Timetable, Inbox and More are direct mobile workspaces.
2. Classes opens assigned classes instead of a generic module grid.
3. More excludes primary navigation modules and deduplicates staff-attendance aliases.
4. Unimplemented staff workflows show an in-app status message rather than launching Chrome.
5. module/metric density no longer reproduces the oversized launcher-card layout.
6. automated Android validation can run successfully when GitHub Actions runners are available.
