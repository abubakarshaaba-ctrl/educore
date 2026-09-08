package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class GradebookCapabilitiesDto(
    val view: Boolean = false,
    @param:Json(name = "edit_form_tutor_remark") val editFormTutorRemark: Boolean = false,
    @param:Json(name = "edit_principal_remark") val editPrincipalRemark: Boolean = false,
    @param:Json(name = "compute_reports") val computeReports: Boolean = false,
    @param:Json(name = "publish_reports") val publishReports: Boolean = false,
)

data class GradebookClassDto(
    val id: Long,
    val name: String,
    @param:Json(name = "class_level_id") val classLevelId: Long? = null,
    @param:Json(name = "class_level") val classLevel: String? = null,
    @param:Json(name = "form_tutor_id") val formTutorId: Long? = null,
    @param:Json(name = "form_tutor") val formTutor: String? = null,
)

data class GradebookTermDto(
    val id: Long,
    val name: String,
    val session: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class GradebookOptionsDto(
    @param:Json(name = "class_arms") val classArms: List<GradebookClassDto> = emptyList(),
    val terms: List<GradebookTermDto> = emptyList(),
)

data class GradebookSelectedDto(
    @param:Json(name = "class_arm_id") val classArmId: Long? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
)

data class GradebookAssessmentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "max_score") val maxScore: Double = 0.0,
    @param:Json(name = "is_exam") val isExam: Boolean = false,
)

data class GradebookSubjectDto(
    val id: Long,
    val name: String,
    val code: String? = null,
)

data class GradebookStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
)

data class GradebookSummaryDto(
    val id: Long,
    val average: Double = 0.0,
    val position: Int? = null,
    @param:Json(name = "class_size") val classSize: Int? = null,
    @param:Json(name = "subjects_offered") val subjectsOffered: Int = 0,
    @param:Json(name = "subjects_failed") val subjectsFailed: Int = 0,
    @param:Json(name = "promotion_status") val promotionStatus: String? = null,
    @param:Json(name = "form_tutor_remark") val formTutorRemark: String? = null,
    @param:Json(name = "principal_remark") val principalRemark: String? = null,
)

data class GradebookSubjectScoreDto(
    @param:Json(name = "subject_id") val subjectId: Long,
    val subject: String,
    val code: String? = null,
    val total: Double = 0.0,
    val assessments: Map<String, Double?> = emptyMap(),
)

data class GradebookStudentRowDto(
    val student: GradebookStudentDto,
    val summary: GradebookSummaryDto? = null,
    val subjects: List<GradebookSubjectScoreDto> = emptyList(),
)

data class GradebookWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: GradebookCapabilitiesDto = GradebookCapabilitiesDto(),
    val options: GradebookOptionsDto = GradebookOptionsDto(),
    val selected: GradebookSelectedDto = GradebookSelectedDto(),
    @param:Json(name = "class") val classRoom: GradebookClassDto? = null,
    val term: GradebookTermDto? = null,
    @param:Json(name = "assessment_types") val assessmentTypes: List<GradebookAssessmentDto> = emptyList(),
    val subjects: List<GradebookSubjectDto> = emptyList(),
    val students: List<GradebookStudentRowDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String? = null,
)

data class GradebookRemarkRequestDto(
    val field: String,
    val remark: String? = null,
)

data class GradebookRemarkResponseDto(
    val message: String,
    @param:Json(name = "summary_id") val summaryId: Long,
    val field: String,
    val remark: String? = null,
    @param:Json(name = "updated_at") val updatedAt: String? = null,
)
