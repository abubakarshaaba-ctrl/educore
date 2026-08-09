# EduCore Universal Data Migration & Portability Engine

## Phase DM-11: enterprise controls

Every institutional migration now has a durable `migration_request` containing its business justification, exact data scope, and risk classification. A School Administrator must approve the request before a platform Migration Administrator can authorize execution. Both decisions are immutable approval records with snapshots, reasons, timestamps, actors, and audit-log entries. Rejections are equally recorded and always require a reason.

The Migration Administrator flag is separate from Super Administrator access. It grants only migration execution, rollback, platform migration oversight, and enterprise reporting; ordinary school administrators retain tenant-scoped request and review access. This separation protects institutional data while allowing the operations team to perform controlled migrations without receiving broad platform administration privileges.

Lifecycle events are written to the `migration_notifications` outbox for reliable in-app, email, or push delivery. The platform dashboard service exposes cross-tenant operational counts, pending approvals, migrations needing attention, unresolved issues, reconciliation failures, and recent activity. School administrators receive the same dashboard constrained to their tenant. Per-migration reports combine source/result totals, request scope, approvals, issue severity, reconciliation evidence, and rollback-journal status.

## Phase DM-10: reconciliation and rollback

Execution mutations must be written to `migration_change_journals` as created, updated, or pre-existing. Updated records retain before-images; mutable records retain after-image checksums. Rollback runs in reverse order and reverses a record only while its current checksum still matches the migration after-image. Drifted, missing, unsupported, or externally irreversible effects become `compensation_required` instead of being overwritten. Only platform migration administrators may execute rollback.

The completion gate combines failed reconciliation scopes, open error/critical issues, expected mutation totals, entity-link totals, and financial reconciliation. A batch cannot move from `reconciling` to `completed` until every gate passes.

## Phase DM-9: bidirectional canonical export

`data-migration:export {tenant} --actor={user} [--entities=students ...]` creates full or selective tenant-scoped ZIP packages. Each entity is streamed to JSONL, hashed independently, and described in a versioned `manifest.json`; the completed archive receives its own SHA-256 checksum and immutable package record. Secrets and tenant database keys are excluded. Internal EduCore IDs are retained only as portable `educore_id` source references so relationships can be reconstructed without confusing source IDs with destination IDs. `DestinationAdapter` separates validation and delivery; DM-9 does not transmit packages automatically.

## Phase DM-8: advanced sources

DM-8 adds content-detected ZIP packages and SQL dump extraction. ZIP inspection rejects absolute/traversal paths, excessive entry counts, excessive expanded size, and suspicious compression ratios; supported CSV/TSV members stream as independent datasets. SQL dumps are tokenized only for simple `INSERT INTO ... (columns) VALUES ...` records and are never executed. Comments and schema commands are not applied.

Database and API integrations implement `ReadOnlySourceConnector`; connector implementations must declare read-only behavior and expose inspection plus resumable streaming. Parquet files are recognized by their `PAR1` signature but remain capability-gated because this installation has no Parquet reader dependency, producing an explicit unsupported-source issue instead of misreading the file.

## Phase DM-7: financial migration planning

DM-7 stages fee structures, invoices, payments, and balances using fixed two-decimal money strings and integer-cent reconciliation. `data-migration:plan-financial {migration} --actor={user}` never posts payments or changes operational balances. Invoice totals, reported paid amounts, successful payment aggregates, currencies, and calculated balances are persisted in the existing reconciliation ledger. Mixed currencies or mismatched paid totals fail reconciliation; reported balances without payment detail require review. Duplicate invoice numbers and gateway references remain conflicts, and all raw monetary strings are preserved.

## Phase DM-6: academic historical migration planning

DM-6 plans student subject registrations, assessment scores, termly summaries/historical grades, and attendance. Assessment types and grading definitions are resolved through the DM-4 blueprint. Run `data-migration:plan-history {migration} --actor={user}` after core planning has placed the batch in `normalising` state.

Historical facts use the actual EduCore composite identities: student/subject/session/term for subject selections; student/subject/assessment/term/session for staged scores; student/term/session for summaries; and student/date for attendance. Duplicate composites with different checksums become conflicts rather than last-write-wins updates.

Every historical record has persisted dependencies. Students resolve through DM-5 core plans or tenant operational records; sessions, terms, class structures, subjects, assessments, and grading structures resolve through DM-4 blueprint nodes. Missing parents, invalid attendance states, negative scores, and out-of-range final averages block the record and create visible issues. Raw values remain intact beside mapped and normalized payloads. Planning is idempotent, audited, and makes no operational writes.

## Phase DM-5: core entity migration planning

DM-5 plans students, guardians, staff, enrolments, and guardian–student relationships. `data-migration:plan-core {migration} --actor={user}` transforms approved field mappings into canonical staging payloads and import-plan records. The plan is deliberately non-executing: it does not create users, generate passwords, insert students or guardians, attach pivots, or write enrolments.

Raw, mapped, and normalized payloads remain separate. Deterministic rules cover whitespace, name casing, canonical gender/status/relationship values, day-first dates, lowercased emails, Nigerian trunk-prefix phone conversion to E.164, booleans, integers, and four-decimal numeric precision. Every applied rule and warning is retained. Unrecognized or ambiguous values are preserved with warnings rather than guessed or discarded.

Core business identities reflect the real schema: tenant admission number for students, globally constrained email for staff accounts, email then phone then name for guardians, and the student/session/term composite for enrolments. Plans are classified as `create`, `update`, `unchanged`, `conflict`, or `blocked`. Duplicate source identities with different checksums are conflicts. A staff email owned by another tenant is a conflict, not a match.

Dependencies are persisted before execution. Enrolments require student, class arm, session, and term; guardian links require both guardian and student. Parents may resolve to an operational record, a core plan, or a reconstructed blueprint proposal. Required fields or parents that remain unresolved block the child and create visible issues. Replanning is idempotent and audited.

## Phase DM-4: school blueprint reconstruction

DM-4 reconstructs structural entities before core and historical records are considered. Supported nodes are academic sessions, terms, class levels, class arms, subjects, assessment types, and grading systems. The registry in `config/data_migration_blueprint.php` declares their real EduCore models, identities, dependency order, relationship fields, and destination foreign keys.

Run `data-migration:blueprint {migration} --actor={user}` after mapping. The command reads canonical field decisions and lossless staged rows, deduplicates structural definitions using deterministic checksums, and compares them only with records owned by the migration tenant. Each node is persisted as `matched`, `proposed`, `ambiguous`, or `conflict`. It never creates or changes a live academic record.

Dependencies are persisted separately. A term references its session, a class arm its class level, an assessment its term, and a grading band its class level. Matching child structures includes the resolved parent foreign key, preventing a same-named term or class in another parent context from being accepted. Missing parents, ambiguous matches, conflicting definitions, and missing identities create visible migration issues. Re-running reconstruction is idempotent.

Manual reviews may match a node to an existing record, preserve it as a proposed creation, or explicitly ignore it with a reason. Cross-tenant matches are rejected and every reconstruction or review is audited.

## Phase DM-3: canonical schema and deterministic mapping

The format-independent, versioned registry in `config/data_migration_schema.php` describes real EduCore fields, aliases, types, required and tenant-unique identifiers, canonical values, and relationship targets. Relationships use portable business references such as admission number and subject code rather than source database primary keys.

`data-migration:map {migration} {dataset} {entity} --actor={user}` profiles staged sample values and evaluates headings, aliases, datatypes, ambiguity, and saved profiles. Defaults are `AUTO_MAP` at 95 or above when unambiguous, `REVIEW` from 75 through 94 (or when ambiguous), and `UNMAPPED` below 75. `IGNORE_EXPLICITLY` is available only as a reviewed decision with a reason.

Every source column receives a persisted decision. Unknown columns remain in `migration_rows.raw_payload` and create an open `unmapped_column` issue; they are never silently discarded. Manual overrides validate the canonical destination, identify the reviewer, resolve the issue, and create a lifecycle audit event.

Reusable profiles are scoped to their tenant, while only platform administrators may create global profiles. Tenant profiles take precedence over global profiles. Mapping is staging-only and never writes operational records.

## Phase DM-1 implementation inventory

EduCore uses a shared database with explicit tenant_id ownership. Tenant resolution is performed by IdentifyTenant and host-resolution middleware. Authorization is provided by role helpers on User, Spatie permissions, and custom staff module grants. Lifecycle and authentication events use the existing audit_logs table. Queue infrastructure is Laravel's configured queue connection. Existing imports are controller-specific CSV/Excel flows and are not replaced in DM-1.

Relevant domains inspected include tenants, users/staff, students, guardians, academic sessions, terms, class levels/arms, subjects, enrolments, assessments/results, attendance, invoices/payments, storage, queues, scheduled commands, audit logging, and current bulk import/export controllers.

## DM-1 scope

DM-1 adds only the reusable safety foundation:

- tenant-scoped migration batches and counters;
- an explicit state machine with guarded transitions;
- immutable source-file metadata and private storage;
- SHA-256 verification using streaming reads;
- datasets and lossless raw row staging;
- deterministic row checksums and idempotent source-row keys;
- mapping decisions, external/internal entity links, issues, checkpoints, approvals, and reconciliation records;
- policy separation between tenant review/request actions and platform-only execution/rollback;
- audit events through EduCore's existing lifecycle audit service;
- read-only Artisan inspection and verification commands.

No adapter parses files in DM-1. No staged row can write to operational EduCore tables. No existing import route has been redirected.

## Core flow

Source -> ImmutableSourceStorage -> MigrationFile -> MigrationDataset -> MigrationRow

Future phases must continue through a canonical DTO, mapping, validation, dry run, approval, and domain services before operational writes.

## State machine

Statuses are defined in App\\Enums\\DataMigrationStatus. Transitions must use MigrationStateMachine; direct status mutation is prohibited by convention. Invalid or concurrent transitions throw InvalidMigrationStateTransition.

Full institutional migrations must not reach approved, queued, or importing without the later dry-run and approval services required by subsequent phases.

## Immutable sources

ImmutableSourceStorage::preserve() verifies tenant ownership, enforces the size limit, computes SHA-256, sanitises the filename, stores on a private tenant/batch path, streams the stored file through checksum verification, records immutable metadata, and writes an audit event.

Original-source fields on MigrationFile cannot be updated through Eloquent. Transformed copies must use separate future artifact records. Configuration is in config/data_migration.php. Migration packages must never use a public disk.

## Zero-silent-data-loss rule

MigrationRowStager stores the complete input row in raw_payload before mapping. Unknown fields remain present. Mapped and normalised payloads are separate nullable columns. A source row is idempotent by dataset_id plus row_number, and its deterministic checksum is independent of associative-key order.

Future mapping code must create an unmapped mapping or issue for every source column that is not mapped or explicitly excluded.

## Authorization

Tenant administrators may create, view, upload, review, and approve batches belonging to their own tenant. Only super administrators may execute or roll back migrations. Cross-tenant creation and uploads are rejected by services even if called outside HTTP controllers.

Tenant-to-tenant movement requires a future dedicated approval workflow and must not be implemented as a direct copy.

## Commands

- php artisan data-migration:inspect {batch-or-id}
- php artisan data-migration:verify {batch-or-id}

Both commands are read-only. Verify recalculates source checksums and fails when an open critical issue exists.

## Operational runbook for DM-1

1. Confirm the target tenant and requester's authorization.
2. Create the batch through MigrationBatchService.
3. Preserve each source through ImmutableSourceStorage.
4. Run data-migration:verify.
5. Inspect batch counts with data-migration:inspect.
6. Do not parse, map, dry-run, approve, or execute until the corresponding later phase is implemented and tested.

## Extension rules

- Source adapters must emit datasets/rows and never write operational tables.
- Canonical DTOs belong between staging and domain services.
- Destination adapters must consume canonical export data.
- Never execute uploaded SQL against EduCore.
- Never trust imported tenant IDs or primary keys.
- Never log source payloads, secrets, or database credentials.
- Use chunking, streaming, and checkpoints for large sources.

## Testing

DataMigrationFoundationTest covers batch auditability, tenant isolation, immutable checksum verification, unknown-field preservation/idempotency, and guarded state transitions.

## Phase DM-2: structured ingestion

DM-2 adds a source-adapter contract and tagged registry. New adapters can be registered by tagging an implementation of App\\Contracts\\DataMigration\\SourceAdapter as data-migration.source-adapters; the ingestion service does not need to change.

Supported structured sources:

- CSV and TSV, streamed with SplFileObject;
- XLSX, XLS and ODS, read with bounded PhpSpreadsheet row filters;
- JSONL, streamed one record per line;
- JSON arrays, objects, and named dataset objects within the configured bounded-memory ceiling;
- XML documents containing repeated first-level row elements, streamed with XMLReader and external-network access disabled.

FileSignatureInspector selects formats from their content signature. Spreadsheet ZIP containers are inspected for XLSX or ODS internals. Unrecognised ZIP archives and binary files are rejected; ZIP archive ingestion belongs to DM-8.

MigrationIngestionService verifies the immutable source checksum before parsing, creates unclassified datasets, preserves every source field in raw_payload, writes rows in configurable chunks, and records extraction checkpoints. A failed batch may resume extraction from its latest dataset checkpoint without duplicating staged rows.

Configuration controls staging chunk size, spreadsheet chunk size, and the ordinary JSON memory ceiling. Large JSON arrays must be converted to JSONL rather than loaded into PHP memory.

The DM-2 command is:

- php artisan data-migration:ingest {batch-or-id} --actor={user-id} [--file={migration-file-id}]

The actor is mandatory. Tenant administrators can ingest only their tenant's batches; super administrators retain platform access. Ingestion ends at extracted and cannot write operational EduCore records.
