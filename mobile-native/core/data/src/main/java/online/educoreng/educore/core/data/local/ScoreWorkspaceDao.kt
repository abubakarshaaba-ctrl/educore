package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface ScoreWorkspaceDao {
    @Query("SELECT * FROM cached_score_contracts WHERE tenant_key = :tenantKey AND user_id = :userId AND cache_key = :cacheKey LIMIT 1")
    suspend fun cache(tenantKey: String, userId: Long, cacheKey: String): CachedScoreContractEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun replaceCache(entity: CachedScoreContractEntity)

    @Query("SELECT * FROM score_drafts WHERE tenant_key = :tenantKey AND user_id = :userId AND class_id = :classId AND subject_id = :subjectId AND term_id = :termId")
    suspend fun drafts(tenantKey: String, userId: Long, classId: Long, subjectId: Long, termId: Long): List<ScoreDraftEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun saveDraft(entity: ScoreDraftEntity)

    @Query("DELETE FROM score_drafts WHERE tenant_key = :tenantKey AND user_id = :userId AND class_id = :classId AND subject_id = :subjectId AND term_id = :termId")
    suspend fun deleteDrafts(tenantKey: String, userId: Long, classId: Long, subjectId: Long, termId: Long)
}
