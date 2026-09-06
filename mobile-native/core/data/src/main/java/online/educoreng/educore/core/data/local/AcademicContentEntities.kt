package online.educoreng.educore.core.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity

@Entity(tableName = "cached_academic_content", primaryKeys = ["tenant_key", "user_id", "cache_key"])
data class CachedAcademicContentEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "cache_key") val cacheKey: String,
    @ColumnInfo(name = "payload_json") val payloadJson: String,
    @ColumnInfo(name = "cached_at_epoch_ms") val cachedAtEpochMs: Long,
)

@Entity(tableName = "lesson_plan_drafts", primaryKeys = ["tenant_key", "user_id", "draft_key"])
data class LessonPlanDraftEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "draft_key") val draftKey: String,
    @ColumnInfo(name = "payload_json") val payloadJson: String,
    @ColumnInfo(name = "updated_at_epoch_ms") val updatedAtEpochMs: Long,
)
