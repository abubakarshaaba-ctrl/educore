package online.educoreng.educore.core.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.Index

@Entity(
    tableName = "pending_sync_operations",
    primaryKeys = ["tenant_key", "user_id", "operation_key"],
    indices = [
        Index(
            value = ["tenant_key", "user_id", "kind", "state", "created_at_epoch_ms"],
            name = "index_pending_sync_scope_kind_state_created",
        ),
    ],
)
data class SyncOperationEntity(
    @ColumnInfo(name = "tenant_key") val tenantKey: String,
    @ColumnInfo(name = "user_id") val userId: Long,
    @ColumnInfo(name = "operation_key") val operationKey: String,
    val kind: String,
    @ColumnInfo(name = "request_id") val requestId: String,
    @ColumnInfo(name = "payload_json") val payloadJson: String,
    val state: String,
    @ColumnInfo(name = "attempt_count") val attemptCount: Int,
    @ColumnInfo(name = "last_error") val lastError: String?,
    @ColumnInfo(name = "created_at_epoch_ms") val createdAtEpochMs: Long,
    @ColumnInfo(name = "updated_at_epoch_ms") val updatedAtEpochMs: Long,
)
