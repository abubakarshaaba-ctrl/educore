# EduCore Curriculum-Grounded Lesson Planner

## Architecture

The existing `lesson_plans` table, model, controller, routes and print views remain the system of record. The upgrade is additive:

- `entry_behaviour` remains nullable for legacy plans and is displayed before previous/background knowledge.
- `structured_plan` stores validated canonical AI output while existing text fields remain populated for old views and exports.
- `curriculum_sources` stores versioned platform or school sources and immutable upload metadata.
- `curriculum_fragments` stores compact deterministic retrieval units.
- `lesson_note_revisions` preserves generated, edited and approved note versions.
- `lesson_note_validations` stores categorical coverage results.
- `ai_usage_logs` stores provider usage metadata without prompts or credentials.

Platform sources have `tenant_id = null`. School sources always have a tenant ID. Retrieval includes only active, approved, effective platform sources and sources belonging to the lesson plan's tenant.

## Lesson plan flow

1. Teacher completes class, subject, topic, timing, sex, average age and other institutional metadata.
2. AI returns the fixed EduCore schema; server-side validation rejects omitted or renamed fields.
3. EduCore maps structured content into legacy editable fields.
4. Teacher reviews and edits the plan.
5. Publishing the plan records approval. Draft plans cannot generate grounded notes.

## Curriculum ingestion and retrieval

1. Platform Super Admin uploads official/common sources from **Curriculum Intelligence**.
2. School Admin or Academic Administrator uploads local authorised sources from **Curriculum Sources**.
3. TXT, CSV and DOCX files are MIME/extension and size validated, checksummed, stored privately, extracted and segmented.
4. Uploaded sources remain inactive until explicit review/activation.
5. Retrieval resolves tenant, subject, class, topic, subtopics and source effective dates.
6. Only matching fragments are sent to the provider, in NERDC-first order.
7. Source trace returned by AI is intersected with retrieved fragment IDs; invented citations are discarded.

## Lesson note and validation flow

1. An approved plan is the immediate generation specification.
2. EduCore retrieves compact authorised evidence.
3. The provider returns structured content blocks, not HTML.
4. EduCore validates the schema and safely renders HTML.
5. Coverage validation checks every plan subtopic and objective and uses `FULL`, `PARTIAL`, `INSUFFICIENT` and `NOT_APPLICABLE` instead of invented percentages.
6. Each attempt creates a new revision; earlier notes are preserved.
7. Missing-content regeneration merges only returned sections into the previous revision.
8. Teacher approval preserves the selected note revision for audit and printing.

## Security controls

- Existing authentication, tenant middleware, model tenant scope and teacher ownership checks remain in force.
- Platform and school curriculum libraries use separate routes and ownership checks.
- School users cannot assign official NERDC/WAEC/NECO/JAMB authority to uploads.
- Provider keys remain server-side in Laravel configuration.
- Uploaded documents are private and executable formats are rejected.
- Structured content is escaped by the renderer; AI HTML and binary images are not trusted.
- Full prompts, API keys and sensitive content are not stored in usage logs.

## Token controls

- Compact fixed prompts and JSON output.
- Topic/subtopic retrieval capped to relevant fragments.
- Note depth controls output budgets: concise, standard and detailed.
- Missing sections are regenerated independently and merged.
- No entire curriculum document is sent to a provider.
- Input/output token usage and latency are recorded when returned by the provider.

## Manual verification

- Open a legacy plan with null entry behaviour; verify edit, display and print still work.
- Create a new plan and verify the institutional order: Sex, Entry Behaviour, Previous / Background Knowledge.
- Confirm a draft plan cannot generate a lesson note.
- Approve a plan, generate a note and inspect the revision and validation panel.
- Generate with no matching source and verify no authority is falsely marked aligned.
- Upload and activate a platform source; verify it is retrievable by an applicable school.
- Upload a private school source and verify another tenant cannot retrieve it.
- Deactivate or expire a source and verify it is excluded.
- Trigger missing-content regeneration and verify the previous revision remains stored.
- Print a legacy note and a structured note.

## Deployment commands

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan test
```

Never use `migrate:fresh` or `db:wipe` for this upgrade.
