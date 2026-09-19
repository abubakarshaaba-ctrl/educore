package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface PortalAttendanceDao {
    @Query(
        "SELECT * FROM cached_portal_attendance " +
            "WHERE tenant_key = :tenantKey AND user_id = :userId AND cache_key = :cacheKey LIMIT 1"
    )
    suspend fun get(
        tenantKey: String,
        userId: Long,
        cacheKey: String,
    ): CachedPortalAttendanceEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun replace(entity: CachedPortalAttendanceEntity)
}
