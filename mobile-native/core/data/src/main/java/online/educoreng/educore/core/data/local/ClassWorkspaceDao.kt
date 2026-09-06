package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Transaction

@Dao
interface ClassWorkspaceDao {
    @Query(
        "SELECT * FROM cached_class_workspaces " +
            "WHERE tenant_key = :tenantKey AND user_id = :userId AND cache_key = :cacheKey LIMIT 1",
    )
    suspend fun cache(tenantKey: String, userId: Long, cacheKey: String): CachedClassWorkspaceEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun replaceCache(entity: CachedClassWorkspaceEntity)

    @Query(
        "SELECT * FROM attendance_drafts WHERE tenant_key = :tenantKey AND user_id = :userId " +
            "AND class_id = :classId AND attendance_date = :date ORDER BY student_id",
    )
    suspend fun attendanceDraft(
        tenantKey: String,
        userId: Long,
        classId: Long,
        date: String,
    ): List<AttendanceDraftEntity>

    @Query(
        "DELETE FROM attendance_drafts WHERE tenant_key = :tenantKey AND user_id = :userId " +
            "AND class_id = :classId AND attendance_date = :date",
    )
    suspend fun deleteAttendanceDraft(tenantKey: String, userId: Long, classId: Long, date: String)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAttendanceDraft(entities: List<AttendanceDraftEntity>)

    @Transaction
    suspend fun replaceAttendanceDraft(
        tenantKey: String,
        userId: Long,
        classId: Long,
        date: String,
        entities: List<AttendanceDraftEntity>,
    ) {
        deleteAttendanceDraft(tenantKey, userId, classId, date)
        if (entities.isNotEmpty()) insertAttendanceDraft(entities)
    }
}
