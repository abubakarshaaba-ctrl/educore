package online.educoreng.educore.core.data.local

import androidx.room.Embedded
import androidx.room.Relation

data class CachedSessionAggregate(
    @Embedded val session: CachedSessionEntity,
    @Relation(parentColumn = "tenant_key", entityColumn = "tenant_key")
    val roles: List<CachedRoleEntity>,
    @Relation(parentColumn = "tenant_key", entityColumn = "tenant_key")
    val permissions: List<CachedPermissionEntity>,
    @Relation(parentColumn = "tenant_key", entityColumn = "tenant_key")
    val features: List<CachedFeatureEntity>,
    @Relation(parentColumn = "tenant_key", entityColumn = "tenant_key")
    val modules: List<CachedModuleEntity>,
)
