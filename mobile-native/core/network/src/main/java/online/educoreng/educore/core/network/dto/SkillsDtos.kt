package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class SkillsCapabilitiesDto(
    val manage: Boolean = false,
    @param:Json(name = "all_classes") val allClasses: Boolean = false,
)

data class SkillsMetricsDto(
    val classes: Int = 0,
    val students: Int = 0,
    val skills: Int = 0,
    @param:Json(name = "rated_entries") val ratedEntries: Int = 0,
)

data class SkillClassOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "form_tutor") val formTutor: String? = null,
    @param:Json(name = "student_count") val studentCount: Int = 0,
)

data class SkillTermOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_id") val sessionId: Long,
    val session: String? = null,
    val current: Boolean = false,
)

data class SkillDefinitionDto(
    val id: Long,
    val name: String,
    val category: String,
    val order: Int = 0,
)

data class SkillRatingScaleDto(val value: Int, val label: String)

data class SkillsWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: SkillsCapabilitiesDto = SkillsCapabilitiesDto(),
    val metrics: SkillsMetricsDto = SkillsMetricsDto(),
    val classes: List<SkillClassOptionDto> = emptyList(),
    val terms: List<SkillTermOptionDto> = emptyList(),
    val skills: List<SkillDefinitionDto> = emptyList(),
    @param:Json(name = "rating_scale") val ratingScale: List<SkillRatingScaleDto> = emptyList(),
)

data class SkillRatingValueDto(
    @param:Json(name = "skill_id") val skillId: Long,
    val rating: Int,
)

data class SkillStudentDto(
    val id: Long,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val name: String,
    val ratings: List<SkillRatingValueDto> = emptyList(),
)

data class SkillSheetClassDto(val id: Long, val name: String)

data class SkillsSheetDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "class") val classInfo: SkillSheetClassDto,
    val term: SkillTermOptionDto,
    val skills: List<SkillDefinitionDto> = emptyList(),
    val students: List<SkillStudentDto> = emptyList(),
    @param:Json(name = "rating_scale") val ratingScale: List<SkillRatingScaleDto> = emptyList(),
    val capabilities: SkillsCapabilitiesDto = SkillsCapabilitiesDto(),
)

data class SkillRatingMutationDto(
    @param:Json(name = "skill_id") val skillId: Long,
    val rating: Int?,
)

data class SkillStudentMutationDto(
    @param:Json(name = "student_id") val studentId: Long,
    val skills: List<SkillRatingMutationDto>,
)

data class SkillsSaveRequestDto(
    @param:Json(name = "class_arm_id") val classArmId: Long,
    @param:Json(name = "term_id") val termId: Long,
    val ratings: List<SkillStudentMutationDto>,
)

data class SkillsSaveResponseDto(
    val message: String,
    val saved: Int = 0,
    val cleared: Int = 0,
)
