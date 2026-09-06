package online.educoreng.educore.core.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity

@Entity(
    tableName = "cached_class_workspaces",
    primaryKeys = ["tenant_key", "user_id", "cache_key"],
)
data class CachedClassWorkspaceEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "cache_key") val cacheKey: String,
    @ColumnInfo(name = "payload_json") val payloadJson: String,
    @ColumnInfo(name = "source_generated_at") val sourceGeneratedAt: String,
    @ColumnInfo(name = "cached_at_epoch_ms") val cachedAtEpochMs: Long,
)

@Entity(
    tableName = "attendance_drafts",
    primaryKeys = ["tenant_key", "user_id", "class_id", "attendance_date", "student_id"],
)
data class AttendanceDraftEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "class_id") val classId: Long,
    @ColumnInfo(name = "attendance_date") val attendanceDate: String,
    @ColumnInfo(name = "student_id") val studentId: Long,
    val status: String?,
    val remark: String?,
    @ColumnInfo(name = "server_version") val serverVersion: String,
    @ColumnInfo(name = "updated_at_epoch_ms") val updatedAtEpochMs: Long,
)
