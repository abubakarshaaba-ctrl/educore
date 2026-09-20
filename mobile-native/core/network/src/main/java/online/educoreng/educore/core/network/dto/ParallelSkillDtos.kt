package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.ParallelSkillArm
import online.educoreng.educore.core.model.ParallelSkillDefinition
import online.educoreng.educore.core.model.ParallelSkillScale
import online.educoreng.educore.core.model.ParallelSkillStudent
import online.educoreng.educore.core.model.ParallelSkillTerm
import online.educoreng.educore.core.model.ParallelSkillWorkspace

data class ParallelSkillWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    @param:Json(name = "selected_arm_id") val selectedArmId: Long? = null,
    @param:Json(name = "selected_term_id") val selectedTermId: Long? = null,
    val arms: List<ParallelSkillArmDto> = emptyList(),
    val terms: List<ParallelSkillTermDto> = emptyList(),
    val skills: List<ParallelSkillDefinitionDto> = emptyList(),
    val students: List<ParallelSkillStudentDto> = emptyList(),
    @param:Json(name = "rating_scale") val ratingScale: List<ParallelSkillScaleDto> = emptyList(),
    val capabilities: ParallelSkillCapabilitiesDto = ParallelSkillCapabilitiesDto(),
)

data class ParallelSkillArmDto(
    val id: Long,
    val name: String,
    @param:Json(name = "class_id") val classId: Long,
    @param:Json(name = "class_name") val className: String? = null,
    @param:Json(name = "curriculum_id") val curriculumId: Long,
    @param:Json(name = "curriculum_name") val curriculumName: String? = null,
    val label: String,
)

data class ParallelSkillTermDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_id") val sessionId: Long,
    @param:Json(name = "session_name") val sessionName: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
    val label: String,
)

data class ParallelSkillDefinitionDto(
    val id: Long,
    val name: String,
    val category: String,
    @param:Json(name = "sort_order") val sortOrder: Int = 0,
)

data class ParallelSkillStudentDto(
    @param:Json(name = "enrolment_id") val enrolmentId: Long,
    @param:Json(name = "student_id") val studentId: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val ratings: List<ParallelSkillSavedRatingDto> = emptyList(),
)

data class ParallelSkillSavedRatingDto(
    @param:Json(name = "skill_id") val skillId: Long,
    val rating: Int,
)

data class ParallelSkillScaleDto(
    val value: Int,
    val label: String,
)

data class ParallelSkillCapabilitiesDto(
    val manage: Boolean = false,
)

data class ParallelSkillSaveRequestDto(
    @param:Json(name = "arm_id") val armId: Long,
    @param:Json(name = "term_id") val termId: Long,
    val ratings: List<ParallelSkillStudentMutationDto>,
)

data class ParallelSkillStudentMutationDto(
    @param:Json(name = "enrolment_id") val enrolmentId: Long,
    val skills: List<ParallelSkillMutationDto>,
)

data class ParallelSkillMutationDto(
    @param:Json(name = "skill_id") val skillId: Long,
    val rating: Int?,
)

data class ParallelSkillSaveResponseDto(
    val message: String,
    val saved: Int = 0,
    val cleared: Int = 0,
)

fun ParallelSkillWorkspaceDto.toDomain(): ParallelSkillWorkspace =
    ParallelSkillWorkspace(
        selectedArmId = selectedArmId,
        selectedTermId = selectedTermId,
        arms = arms.map {
            ParallelSkillArm(
                id = it.id,
                name = it.name,
                classId = it.classId,
                className = it.className,
                curriculumId = it.curriculumId,
                curriculumName = it.curriculumName,
                label = it.label,
            )
        },
        terms = terms.map {
            ParallelSkillTerm(
                id = it.id,
                name = it.name,
                sessionId = it.sessionId,
                sessionName = it.sessionName,
                isCurrent = it.isCurrent,
                label = it.label,
            )
        },
        skills = skills.map {
            ParallelSkillDefinition(
                id = it.id,
                name = it.name,
                category = it.category,
                sortOrder = it.sortOrder,
            )
        },
        students = students.map { student ->
            ParallelSkillStudent(
                enrolmentId = student.enrolmentId,
                studentId = student.studentId,
                name = student.name,
                admissionNumber = student.admissionNumber,
                ratings = student.ratings.associate { it.skillId to it.rating },
            )
        },
        ratingScale = ratingScale.map {
            ParallelSkillScale(value = it.value, label = it.label)
        },
        canManage = capabilities.manage,
    )

