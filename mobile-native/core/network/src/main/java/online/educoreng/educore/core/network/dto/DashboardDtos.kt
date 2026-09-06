package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class DashboardResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val scope: String,
    @param:Json(name = "role_key") val roleKey: String,
    @param:Json(name = "generated_at") val generatedAt: String,
    val metrics: List<DashboardMetricDto> = emptyList(),
    @param:Json(name = "quick_actions") val quickActions: List<DashboardActionDto> = emptyList(),
    val sections: List<DashboardSectionDto> = emptyList(),
)

data class DashboardMetricDto(
    val key: String,
    val label: String,
    @param:Json(name = "display_value") val displayValue: String,
    val tone: String = "neutral",
    @param:Json(name = "module_key") val moduleKey: String? = null,
)

data class DashboardActionDto(
    @param:Json(name = "module_key") val moduleKey: String,
    val title: String,
    val icon: String,
    val path: String,
)

data class DashboardSectionDto(
    val key: String,
    val title: String,
    val items: List<DashboardItemDto> = emptyList(),
)

data class DashboardItemDto(
    val id: String,
    val title: String,
    val subtitle: String? = null,
    @param:Json(name = "supporting_text") val supportingText: String? = null,
    val status: String? = null,
    val timestamp: String? = null,
    @param:Json(name = "module_key") val moduleKey: String? = null,
)
