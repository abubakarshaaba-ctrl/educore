package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface SyncOperationDao {
    @Query("SELECT * FROM pending_sync_operations WHERE tenant_key=:tenantKey AND user_id=:userId AND operation_key=:key LIMIT 1")
    suspend fun get(tenantKey: String, userId: Long, key: String): SyncOperationEntity?

    @Query("SELECT * FROM pending_sync_operations WHERE tenant_key=:tenantKey AND user_id=:userId AND kind=:kind AND state IN ('pending','syncing','failed') ORDER BY created_at_epoch_ms")
    suspend fun actionable(tenantKey: String, userId: Long, kind: String): List<SyncOperationEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun upsert(entity: SyncOperationEntity)

    @Query("DELETE FROM pending_sync_operations WHERE tenant_key=:tenantKey AND user_id=:userId AND operation_key=:key")
    suspend fun delete(tenantKey: String, userId: Long, key: String)
}
