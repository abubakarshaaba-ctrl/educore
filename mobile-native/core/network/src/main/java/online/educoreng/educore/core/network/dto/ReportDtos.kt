package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class ReportModuleDto(
    val key: String,
    val title: String,
)

data class ReportCapabilitiesDto(
    val view: Boolean = false,
    val compute: Boolean = false,
    val publish: Boolean = false,
    val unpublish: Boolean = false,
    @param:Json(name = "edit_remarks") val editRemarks: Boolean = false,
)

data class ReportClassOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "class_level_id") val classLevelId: Long? = null,
)

data class ReportTermOptionDto(
    val id: Long,
    val name: String,
    val session: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class ReportOptionsDto(
    @param:Json(name = "class_arms") val classArms: List<ReportClassOptionDto> = emptyList(),
    val terms: List<ReportTermOptionDto> = emptyList(),
)

data class ReportSelectedDto(
    @param:Json(name = "class_arm_id") val classArmId: Long? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
)

data class ReportSelectionDto(
    val id: Long,
    val name: String,
    val session: String? = null,
)

data class ReportPublicationDto(
    val id: Long,
    val status: String,
    @param:Json(name = "published_at") val publishedAt: String? = null,
    @param:Json(name = "published_by") val publishedBy: Long? = null,
    @param:Json(name = "published_by_name") val publishedByName: String? = null,
    @param:Json(name = "archived_at") val archivedAt: String? = null,
    val note: String? = null,
)

data class ReportWorkspaceSummaryDto(
    val computed: Int = 0,
    @param:Json(name = "active_students") val activeStudents: Int? = null,
    val missing: Int? = null,
    val published: Boolean = false,
)

data class ReportStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
)

data class ReportSummaryRowDto(
    @param:Json(name = "summary_id") val summaryId: Long,
    val student: ReportStudentDto,
    val average: Double = 0.0,
    val position: Int? = null,
    @param:Json(name = "class_size") val classSize: Int? = null,
    @param:Json(name = "subjects_offered") val subjectsOffered: Int = 0,
    @param:Json(name = "subjects_failed") val subjectsFailed: Int = 0,
    @param:Json(name = "promotion_status") val promotionStatus: String? = null,
    @param:Json(name = "form_tutor_remark") val formTutorRemark: String? = null,
    @param:Json(name = "principal_remark") val principalRemark: String? = null,
    @param:Json(name = "computed_at") val computedAt: String? = null,
)

data class ReportsWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val module: ReportModuleDto,
    val capabilities: ReportCapabilitiesDto = ReportCapabilitiesDto(),
    val options: ReportOptionsDto = ReportOptionsDto(),
    val selected: ReportSelectedDto = ReportSelectedDto(),
    @param:Json(name = "class") val classRoom: ReportSelectionDto? = null,
    val term: ReportSelectionDto? = null,
    val publication: ReportPublicationDto? = null,
    val summary: ReportWorkspaceSummaryDto = ReportWorkspaceSummaryDto(),
    val students: List<ReportSummaryRowDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String? = null,
)

data class ReportPublishRequestDto(
    @param:Json(name = "class_arm_id") val classArmId: Long,
    @param:Json(name = "term_id") val termId: Long,
    val note: String? = null,
)

data class ReportMutationResponseDto(
    val message: String,
    val publication: ReportPublicationDto,
)
