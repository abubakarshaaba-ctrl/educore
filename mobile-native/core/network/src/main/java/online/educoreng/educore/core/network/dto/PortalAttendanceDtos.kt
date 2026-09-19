package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.PortalAttendanceChild
import online.educoreng.educore.core.model.PortalAttendanceProgramme
import online.educoreng.educore.core.model.PortalAttendanceRecord
import online.educoreng.educore.core.model.PortalAttendanceSection
import online.educoreng.educore.core.model.PortalAttendanceStudent
import online.educoreng.educore.core.model.PortalAttendanceSummary
import online.educoreng.educore.core.model.PortalAttendanceTerm
import online.educoreng.educore.core.model.PortalAttendanceWorkspace

data class PortalAttendanceResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    val student: PortalAttendanceStudentDto,
    val children: List<PortalAttendanceChildDto> = emptyList(),
    val terms: List<PortalAttendanceTermDto> = emptyList(),
    @param:Json(name = "selected_term_id") val selectedTermId: Long? = null,
    val conventional: PortalAttendanceSectionDto,
    @param:Json(name = "parallel_programmes") val parallelProgrammes: List<PortalAttendanceProgrammeDto> = emptyList(),
)

data class PortalAttendanceStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
)

data class PortalAttendanceChildDto(
    val id: Long,
    val name: String,
)

data class PortalAttendanceTermDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_name") val sessionName: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class PortalAttendanceSummaryDto(
    val total: Int = 0,
    val present: Int = 0,
    val absent: Int = 0,
    val late: Int = 0,
    val excused: Int = 0,
    val rate: Double = 0.0,
)

data class PortalAttendanceRecordDto(
    val date: String? = null,
    val status: String,
    val remark: String? = null,
)

data class PortalAttendanceSectionDto(
    val stats: PortalAttendanceSummaryDto,
    val records: List<PortalAttendanceRecordDto> = emptyList(),
)

data class PortalAttendanceProgrammeDto(
    @param:Json(name = "curriculum_id") val curriculumId: Long,
    @param:Json(name = "curriculum_name") val curriculumName: String,
    @param:Json(name = "class_name") val className: String? = null,
    @param:Json(name = "arm_name") val armName: String? = null,
    val stats: PortalAttendanceSummaryDto,
    val records: List<PortalAttendanceRecordDto> = emptyList(),
)

fun PortalAttendanceResponseDto.toDomain(fromCache: Boolean = false): PortalAttendanceWorkspace =
    PortalAttendanceWorkspace(
        student = PortalAttendanceStudent(
            id = student.id,
            name = student.name,
            admissionNumber = student.admissionNumber,
        ),
        children = children.map { PortalAttendanceChild(it.id, it.name) },
        terms = terms.map {
            PortalAttendanceTerm(
                id = it.id,
                name = it.name,
                sessionName = it.sessionName,
                isCurrent = it.isCurrent,
            )
        },
        selectedTermId = selectedTermId,
        conventional = PortalAttendanceSection(
            stats = conventional.stats.toDomain(),
            records = conventional.records.map { it.toDomain() },
        ),
        parallelProgrammes = parallelProgrammes.map { programme ->
            PortalAttendanceProgramme(
                curriculumId = programme.curriculumId,
                curriculumName = programme.curriculumName,
                className = programme.className,
                armName = programme.armName,
                stats = programme.stats.toDomain(),
                records = programme.records.map { it.toDomain() },
            )
        },
        isFromCache = fromCache,
    )

private fun PortalAttendanceSummaryDto.toDomain() =
    PortalAttendanceSummary(total, present, absent, late, excused, rate)

private fun PortalAttendanceRecordDto.toDomain() =
    PortalAttendanceRecord(date, status, remark)
