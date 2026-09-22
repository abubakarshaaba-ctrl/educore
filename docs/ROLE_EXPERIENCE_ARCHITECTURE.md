# EduCore Role Experience Architecture

## Objective

EduCore should expose the task a user needs before exposing the platform's underlying module hierarchy. The authorization model remains the source of truth; this layer changes presentation and prioritization only.

## Core experiences

| Experience | Home emphasis | Primary tab | Secondary tab | Priority tasks |
| --- | --- | --- | --- | --- |
| Platform administrator | Platform pulse | Schools | Operations | Schools, broadcasts, support, analytics |
| School administrator | School pulse | Academics | Operations | Attendance, scores, reports, staff attendance |
| Teacher | Today at a glance | Classes | Timetable | Attendance, scores, lesson planner, classes |
| Student | My progress | Academics | Timetable | Timetable, attendance, results, subjects |
| Parent | Family overview | Children | Academics | Attendance, results, fees, messages |

Specialist roles such as Accountant, Health Officer and Transport Officer retain focused workspaces and are not forced into a teaching or administration experience.

## Guardrails

1. **Authorization before presentation.** `MobileModuleService` determines which modules are authorized. `MobileRoleExperiencePolicy` may reorder or relabel only those modules.
2. **No invented destinations.** Native shell tabs contain backend-provided modules only.
3. **More remains the complete hub.** Task-first tabs reduce cognitive load without hiding authorized functionality.
4. **Role labels are stable.** A core role receives predictable tab labels rather than labels changing because one optional module is unavailable.
5. **No workflow rewrite.** Academic lifecycle, CBT, parallel curriculum, attendance, finance, communication and notification logic remain unchanged.

## Implementation

- `MobileRoleExperiencePolicy` defines server-side quick-action priorities and task-oriented aliases.
- `MobileDashboardService` filters through the existing module authorization service first, then applies presentation priority.
- `ShellNavigationPolicy` gives the five core experiences stable tab semantics and prevents platform school modules from being duplicated between primary and operations tabs.
- `DashboardHomeContent` uses role-aware overview and quick-action copy while preserving existing responsive dashboard components and cached/offline behavior.

## Web dashboard alignment

The web dashboards use the same task-first presentation rules without changing authorization:

- Platform administrator home promotes Manage Schools, Send Broadcast, Support Inbox and Platform Analytics.
- School administrator home promotes Student Attendance, Enter Scores, Report Cards, Staff Attendance, Students and Messages when authorized.
- The canonical teacher self-service dashboard promotes Mark Attendance, Enter Scores, Lesson Planner, My Classes, My Timetable and Messages when those modules are authorized.
- Student home promotes My Timetable, My Attendance, My Results and My Subjects.
- Parent home promotes Child Attendance, Child Results, Fees & Payments and Child Timetable.
- Less frequent functions remain available through the existing role/permission-filtered navigation rather than competing for primary dashboard space.

## Next extension points

Apply progressive disclosure to the full web sidebar so high-frequency task groups appear first while every authorized module remains discoverable. New modules should enter the complete authorized module hub first and be promoted into role-specific primary navigation only when they become frequent tasks.
