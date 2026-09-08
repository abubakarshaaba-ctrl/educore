package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class ExportModuleDto(
    val key: String,
    val title: String,
    val description: String,
    @param:Json(name = "mobile_policy") val mobilePolicy: String,
)

data class ExportCapabilitiesDto(
    val students: Boolean = false,
    val broadsheet: Boolean = false,
    val fees: Boolean = false,
)

data class ExportClassOptionDto(
    val id: Long,
    val name: String,
)

data class ExportTermOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_id") val sessionId: Long,
    @param:Json(name = "session_name") val sessionName: String? = null,
    val current: Boolean = false,
)

data class ExportSessionOptionDto(
    val id: Long,
    val name: String,
    val current: Boolean = false,
)

data class ExportOptionsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val module: ExportModuleDto,
    val capabilities: ExportCapabilitiesDto = ExportCapabilitiesDto(),
    val classes: List<ExportClassOptionDto> = emptyList(),
    val terms: List<ExportTermOptionDto> = emptyList(),
    val sessions: List<ExportSessionOptionDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String,
)
