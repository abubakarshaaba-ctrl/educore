package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface AcademicContentDao {
    @Query("SELECT * FROM cached_academic_content WHERE tenant_key = :tenantKey AND user_id = :userId AND cache_key = :cacheKey LIMIT 1")
    suspend fun cache(tenantKey: String, userId: Long, cacheKey: String): CachedAcademicContentEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun replaceCache(entity: CachedAcademicContentEntity)

    @Query("SELECT * FROM lesson_plan_drafts WHERE tenant_key = :tenantKey AND user_id = :userId AND draft_key = :draftKey LIMIT 1")
    suspend fun draft(tenantKey: String, userId: Long, draftKey: String): LessonPlanDraftEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun replaceDraft(entity: LessonPlanDraftEntity)

    @Query("DELETE FROM lesson_plan_drafts WHERE tenant_key = :tenantKey AND user_id = :userId AND draft_key = :draftKey")
    suspend fun deleteDraft(tenantKey: String, userId: Long, draftKey: String)
}
