package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class AcademicCycleCapabilitiesDto(val manage: Boolean = false)

data class AcademicCycleCurrentDto(
    @param:Json(name = "session_id") val sessionId: Long? = null,
    val session: String? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
    val term: String? = null,
)

data class AcademicCycleMetricsDto(
    val sessions: Int = 0,
    val terms: Int = 0,
    @param:Json(name = "current_session_ready") val currentSessionReady: Boolean = false,
    @param:Json(name = "current_term_ready") val currentTermReady: Boolean = false,
)

data class AcademicSessionAdminDto(
    val id: Long,
    val name: String,
    val current: Boolean = false,
    @param:Json(name = "term_count") val termCount: Int = 0,
)

data class AcademicTermAdminDto(
    val id: Long,
    @param:Json(name = "session_id") val sessionId: Long,
    val session: String? = null,
    val name: String,
    @param:Json(name = "start_date") val startDate: String? = null,
    @param:Json(name = "end_date") val endDate: String? = null,
    @param:Json(name = "next_term_begins") val nextTermBegins: String? = null,
    val current: Boolean = false,
)

data class AcademicCycleWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: AcademicCycleCapabilitiesDto = AcademicCycleCapabilitiesDto(),
    val current: AcademicCycleCurrentDto = AcademicCycleCurrentDto(),
    val metrics: AcademicCycleMetricsDto = AcademicCycleMetricsDto(),
    val sessions: List<AcademicSessionAdminDto> = emptyList(),
    val terms: List<AcademicTermAdminDto> = emptyList(),
)

data class AcademicSessionCreateRequestDto(
    val name: String,
    val activate: Boolean = false,
)

data class AcademicSessionUpdateRequestDto(val name: String)

data class AcademicTermCreateRequestDto(
    @param:Json(name = "session_id") val sessionId: Long,
    val name: String,
    @param:Json(name = "start_date") val startDate: String,
    @param:Json(name = "end_date") val endDate: String,
    @param:Json(name = "next_term_begins") val nextTermBegins: String? = null,
    val activate: Boolean = false,
)

data class AcademicTermUpdateRequestDto(
    val name: String,
    @param:Json(name = "start_date") val startDate: String,
    @param:Json(name = "end_date") val endDate: String,
    @param:Json(name = "next_term_begins") val nextTermBegins: String? = null,
)

data class AcademicCycleReadinessDto(
    val allowed: Boolean = false,
    val blocking: List<String> = emptyList(),
    val warnings: List<String> = emptyList(),
    val information: List<String> = emptyList(),
)

data class AcademicSessionMutationResponseDto(
    val message: String,
    val session: AcademicSessionAdminDto,
)

data class AcademicTermMutationResponseDto(
    val message: String,
    val term: AcademicTermAdminDto,
)

data class AcademicCycleMessageDto(val message: String)
