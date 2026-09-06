package online.educoreng.educore.core.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity

@Entity(tableName = "cached_score_contracts", primaryKeys = ["tenant_key", "user_id", "cache_key"])
data class CachedScoreContractEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "cache_key") val cacheKey: String,
    @ColumnInfo(name = "payload_json") val payloadJson: String,
    @ColumnInfo(name = "cached_at_epoch_ms") val cachedAtEpochMs: Long,
)

@Entity(
    tableName = "score_drafts",
    primaryKeys = ["tenant_key", "user_id", "class_id", "subject_id", "term_id", "student_id", "assessment_id"],
)
data class ScoreDraftEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "class_id") val classId: Long,
    @ColumnInfo(name = "subject_id") val subjectId: Long,
    @ColumnInfo(name = "term_id") val termId: Long,
    @ColumnInfo(name = "student_id") val studentId: Long,
    @ColumnInfo(name = "assessment_id") val assessmentId: Long,
    val value: Double?,
    @ColumnInfo(name = "server_version") val serverVersion: String,
    @ColumnInfo(name = "updated_at_epoch_ms") val updatedAtEpochMs: Long,
)
