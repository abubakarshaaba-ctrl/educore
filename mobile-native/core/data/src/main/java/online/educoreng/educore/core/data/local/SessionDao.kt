package online.educoreng.educore.core.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Transaction
import kotlinx.coroutines.flow.Flow

@Dao
interface SessionDao {
    @Transaction
    @Query("SELECT * FROM cached_sessions WHERE tenant_key = :tenantKey LIMIT 1")
    fun observe(tenantKey: String): Flow<CachedSessionAggregate?>

    @Query("SELECT * FROM cached_sessions WHERE tenant_key = :tenantKey LIMIT 1")
    suspend fun get(tenantKey: String): CachedSessionEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertSession(entity: CachedSessionEntity)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertRoles(entities: List<CachedRoleEntity>)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertPermissions(entities: List<CachedPermissionEntity>)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertFeatures(entities: List<CachedFeatureEntity>)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertModules(entities: List<CachedModuleEntity>)

    @Query("DELETE FROM cached_roles WHERE tenant_key = :tenantKey")
    suspend fun deleteRoles(tenantKey: String)

    @Query("DELETE FROM cached_permissions WHERE tenant_key = :tenantKey")
    suspend fun deletePermissions(tenantKey: String)

    @Query("DELETE FROM cached_features WHERE tenant_key = :tenantKey")
    suspend fun deleteFeatures(tenantKey: String)

    @Query("DELETE FROM cached_modules WHERE tenant_key = :tenantKey")
    suspend fun deleteModules(tenantKey: String)

    @Transaction
    suspend fun replace(
        session: CachedSessionEntity,
        roles: List<CachedRoleEntity>,
        permissions: List<CachedPermissionEntity>,
        features: List<CachedFeatureEntity>,
        modules: List<CachedModuleEntity>,
    ) {
        deleteRoles(session.tenantKey)
        deletePermissions(session.tenantKey)
        deleteFeatures(session.tenantKey)
        deleteModules(session.tenantKey)
        insertSession(session)
        if (roles.isNotEmpty()) insertRoles(roles)
        if (permissions.isNotEmpty()) insertPermissions(permissions)
        if (features.isNotEmpty()) insertFeatures(features)
        if (modules.isNotEmpty()) insertModules(modules)
    }
}
