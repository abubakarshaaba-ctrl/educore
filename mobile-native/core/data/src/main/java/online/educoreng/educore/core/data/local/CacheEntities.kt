package online.educoreng.educore.core.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "cached_sessions")
data class CachedSessionEntity(
    @PrimaryKey @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "user_name") val userName: String,
    @ColumnInfo(name = "user_email") val userEmail: String?,
    @ColumnInfo(name = "staff_id") val staffId: String?,
    @ColumnInfo(name = "role_key") val roleKey: String,
    @ColumnInfo(name = "role_label") val roleLabel: String,
    val portal: String,
    @ColumnInfo(name = "school_id") val schoolId: Long?,
    @ColumnInfo(name = "school_name") val schoolName: String,
    @ColumnInfo(name = "school_slug") val schoolSlug: String,
    @ColumnInfo(name = "school_primary_color") val schoolPrimaryColor: String,
    @ColumnInfo(name = "school_accent_color") val schoolAccentColor: String,
    @ColumnInfo(name = "school_motto") val schoolMotto: String?,
    @ColumnInfo(name = "session_id") val sessionId: Long?,
    @ColumnInfo(name = "session_name") val sessionName: String?,
    @ColumnInfo(name = "term_id") val termId: Long?,
    @ColumnInfo(name = "term_name") val termName: String?,
    @ColumnInfo(name = "access_allowed") val accessAllowed: Boolean,
    @ColumnInfo(name = "access_state") val accessState: String,
    @ColumnInfo(name = "access_message") val accessMessage: String,
    @ColumnInfo(name = "access_severity") val accessSeverity: String?,
    @ColumnInfo(name = "access_expires_at") val accessExpiresAt: String?,
    @ColumnInfo(name = "server_time") val serverTime: String?,
    @ColumnInfo(name = "contract_version") val contractVersion: Int,
    @ColumnInfo(name = "token_expires_at") val tokenExpiresAt: String?,
    @ColumnInfo(name = "cached_at_epoch_ms") val cachedAtEpochMs: Long,
)

@Entity(
    tableName = "cached_roles",
    primaryKeys = ["tenant_key", "name"],
)
data class CachedRoleEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    val name: String,
)

@Entity(
    tableName = "cached_permissions",
    primaryKeys = ["tenant_key", "name"],
)
data class CachedPermissionEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    val name: String,
)

@Entity(
    tableName = "cached_features",
    primaryKeys = ["tenant_key", "name"],
)
data class CachedFeatureEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    val name: String,
)

@Entity(
    tableName = "cached_modules",
    primaryKeys = ["tenant_key", "key"],
)
data class CachedModuleEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    val key: String,
    val title: String,
    val path: String,
    val icon: String,
    @ColumnInfo(name = "sort_order") val sortOrder: Int,
)

@Entity(
    tableName = "cached_dashboards",
    primaryKeys = ["tenant_key", "user_id"],
)
data class CachedDashboardEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    val scope: String,
    @ColumnInfo(name = "payload_json") val payloadJson: String,
    @ColumnInfo(name = "source_generated_at") val sourceGeneratedAt: String,
    @ColumnInfo(name = "cached_at_epoch_ms") val cachedAtEpochMs: Long,
)
