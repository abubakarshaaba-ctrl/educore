package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface DashboardDao {
    @Query("SELECT * FROM cached_dashboards WHERE tenant_key = :tenantKey AND user_id = :userId LIMIT 1")
    suspend fun get(tenantKey: String, userId: Long): CachedDashboardEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun replace(entity: CachedDashboardEntity)
}
