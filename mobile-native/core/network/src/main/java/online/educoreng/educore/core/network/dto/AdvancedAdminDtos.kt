package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class AdvancedAdminOverviewDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    val scope: String = "tenant",
    @param:Json(name = "selected_tenant_id") val selectedTenantId: Long? = null,
    val capabilities: AdvancedAdminCapabilitiesDto = AdvancedAdminCapabilitiesDto(),
    val tenants: List<AdvancedAdminTenantDto> = emptyList(),
    val migrations: List<AdvancedAdminMigrationDto> = emptyList(),
    @param:Json(name = "migration_requests") val migrationRequests: List<AdvancedAdminMigrationRequestDto> = emptyList(),
    @param:Json(name = "audit_stats") val auditStats: AdvancedAdminAuditStatsDto = AdvancedAdminAuditStatsDto(),
    val audit: List<AdvancedAdminAuditDto> = emptyList(),
    val backups: List<AdvancedAdminBackupDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String? = null,
)

data class AdvancedAdminCapabilitiesDto(
    @param:Json(name = "create_migration") val createMigration: Boolean = false,
    @param:Json(name = "ingest_migration") val ingestMigration: Boolean = false,
    @param:Json(name = "verify_migration") val verifyMigration: Boolean = false,
    @param:Json(name = "reconstruct_blueprint") val reconstructBlueprint: Boolean = false,
    @param:Json(name = "approve_school") val approveSchool: Boolean = false,
    @param:Json(name = "approve_platform") val approvePlatform: Boolean = false,
    @param:Json(name = "run_backup") val runBackup: Boolean = false,
)

data class AdvancedAdminTenantDto(
    val id: Long,
    val name: String,
)

data class AdvancedAdminMigrationDto(
    val id: Long,
    @param:Json(name = "batch_number") val batchNumber: String,
    @param:Json(name = "tenant_id") val tenantId: Long,
    @param:Json(name = "tenant_name") val tenantName: String? = null,
    val direction: String,
    @param:Json(name = "migration_type") val migrationType: String,
    @param:Json(name = "source_system") val sourceSystem: String? = null,
    val status: String,
    @param:Json(name = "files_count") val filesCount: Int = 0,
    @param:Json(name = "datasets_count") val datasetsCount: Int = 0,
    @param:Json(name = "issues_count") val issuesCount: Int = 0,
    @param:Json(name = "total_source_rows") val totalSourceRows: Int = 0,
    @param:Json(name = "total_created") val totalCreated: Int = 0,
    @param:Json(name = "total_updated") val totalUpdated: Int = 0,
    @param:Json(name = "total_failed") val totalFailed: Int = 0,
    @param:Json(name = "created_at") val createdAt: String? = null,
    @param:Json(name = "completed_at") val completedAt: String? = null,
)

data class AdvancedAdminMigrationRequestDto(
    val id: Long,
    @param:Json(name = "migration_id") val migrationId: Long,
    @param:Json(name = "batch_number") val batchNumber: String? = null,
    @param:Json(name = "tenant_id") val tenantId: Long,
    val status: String,
    @param:Json(name = "risk_level") val riskLevel: String? = null,
    @param:Json(name = "business_justification") val businessJustification: String? = null,
    @param:Json(name = "data_scope") val dataScope: List<String> = emptyList(),
    @param:Json(name = "decision_reason") val decisionReason: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
)

data class AdvancedAdminAuditStatsDto(
    @param:Json(name = "events_7d") val events7d: Int = 0,
    @param:Json(name = "unique_actors_7d") val uniqueActors7d: Int = 0,
    @param:Json(name = "security_signals_7d") val securitySignals7d: Int = 0,
)

data class AdvancedAdminAuditDto(
    val id: Long,
    val action: String,
    val actor: String? = null,
    val tenant: String? = null,
    val reason: String? = null,
    @param:Json(name = "ip_address") val ipAddress: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
)

data class AdvancedAdminBackupDto(
    val name: String,
    val size: Long = 0,
    @param:Json(name = "created_at") val createdAt: String? = null,
)

data class AdvancedAdminMessageDto(
    val ok: Boolean? = null,
    val message: String,
    val status: String? = null,
)

data class AdvancedAdminMigrationCreatedDto(
    val message: String,
    @param:Json(name = "migration_id") val migrationId: Long,
    @param:Json(name = "batch_number") val batchNumber: String,
    @param:Json(name = "request_id") val requestId: Long,
    @param:Json(name = "request_status") val requestStatus: String,
)

data class AdvancedAdminConfirmationDto(
    val confirmation: String,
)

data class AdvancedAdminDecisionDto(
    val reason: String,
)
