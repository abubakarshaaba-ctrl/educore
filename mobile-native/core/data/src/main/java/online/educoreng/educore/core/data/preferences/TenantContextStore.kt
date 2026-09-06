package online.educoreng.educore.core.data.preferences

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.eduCoreDataStore by preferencesDataStore(name = "educore_context")

class TenantContextStore(private val context: Context) {
    val activeTenantKey: Flow<String?> = context.eduCoreDataStore.data
        .map { preferences -> preferences[ACTIVE_TENANT_KEY] }

    suspend fun setActiveTenant(tenantKey: String) {
        require(tenantKey.isNotBlank())
        context.eduCoreDataStore.edit { it[ACTIVE_TENANT_KEY] = tenantKey }
    }

    suspend fun clear() {
        context.eduCoreDataStore.edit { it.remove(ACTIVE_TENANT_KEY) }
    }

    private companion object {
        val ACTIVE_TENANT_KEY = stringPreferencesKey("active_tenant_key")
    }
}
