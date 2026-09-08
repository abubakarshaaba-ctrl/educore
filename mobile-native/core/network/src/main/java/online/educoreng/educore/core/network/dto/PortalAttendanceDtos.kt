package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PortalAttendanceClassDto(
    val id: Long,
    val name: String,
)

data class PortalAttendanceStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val status: String? = null,
    @param:Json(name = "class") val classRoom: PortalAttendanceClassDto? = null,
)

data class PortalAttendanceTermDto(
    val id: Long,
    val name: String,
    val session: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class PortalAttendanceRecordDto(
    val date: String,
    val status: String,
    val remark: String? = null,
)

data class PortalAttendanceStatsDto(
    val total: Int = 0,
    val present: Int = 0,
    val absent: Int = 0,
    val late: Int = 0,
    val rate: Double = 0.0,
)

data class PortalAttendanceResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val portal: String,
    val student: PortalAttendanceStudentDto? = null,
    val children: List<PortalAttendanceStudentDto> = emptyList(),
    val terms: List<PortalAttendanceTermDto> = emptyList(),
    @param:Json(name = "selected_term_id") val selectedTermId: Long? = null,
    val records: List<PortalAttendanceRecordDto> = emptyList(),
    val stats: PortalAttendanceStatsDto = PortalAttendanceStatsDto(),
    @param:Json(name = "generated_at") val generatedAt: String? = null,
)
