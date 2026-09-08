package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class InventoryCapabilitiesDto(val manage: Boolean = false)

data class InventoryMetricsDto(
    val total: Int = 0,
    @param:Json(name = "in_use") val inUse: Int = 0,
    @param:Json(name = "under_repair") val underRepair: Int = 0,
    val damaged: Int = 0,
)

data class InventoryAssetDto(
    val id: Long,
    val name: String,
    val category: String? = null,
    @param:Json(name = "serial_number") val serialNumber: String? = null,
    val location: String? = null,
    @param:Json(name = "assigned_to") val assignedTo: Long? = null,
    @param:Json(name = "assigned_to_name") val assignedToName: String? = null,
    @param:Json(name = "purchase_date") val purchaseDate: String? = null,
    @param:Json(name = "purchase_cost") val purchaseCost: Double? = null,
    val condition: String,
    val status: String,
    val notes: String? = null,
)

data class InventoryStaffDto(
    val id: Long,
    val name: String,
    @param:Json(name = "staff_id") val staffId: String? = null,
)

data class InventoryOptionDto(val key: String, val label: String)

data class InventorySelectedDto(val search: String = "", val status: String = "all")

data class InventoryMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 40,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class InventoryWorkspaceDto(
    val capabilities: InventoryCapabilitiesDto = InventoryCapabilitiesDto(),
    val metrics: InventoryMetricsDto = InventoryMetricsDto(),
    val assets: List<InventoryAssetDto> = emptyList(),
    val staff: List<InventoryStaffDto> = emptyList(),
    @param:Json(name = "status_options") val statusOptions: List<InventoryOptionDto> = emptyList(),
    @param:Json(name = "condition_options") val conditionOptions: List<InventoryOptionDto> = emptyList(),
    val selected: InventorySelectedDto = InventorySelectedDto(),
    val meta: InventoryMetaDto = InventoryMetaDto(),
)

data class InventoryAssetRequestDto(
    val name: String? = null,
    val category: String? = null,
    @param:Json(name = "serial_number") val serialNumber: String? = null,
    val location: String? = null,
    @param:Json(name = "assigned_to") val assignedTo: Long? = null,
    @param:Json(name = "purchase_date") val purchaseDate: String? = null,
    @param:Json(name = "purchase_cost") val purchaseCost: Double? = null,
    val condition: String? = null,
    val status: String? = null,
    val notes: String? = null,
)

data class InventoryAssetResponseDto(
    val message: String,
    val asset: InventoryAssetDto,
)

data class InventoryMutationResponseDto(val message: String)
