package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.ParallelLifecycleArm
import online.educoreng.educore.core.model.ParallelLifecycleArmSubjectTeacher
import online.educoreng.educore.core.model.ParallelLifecycleClass
import online.educoreng.educore.core.model.ParallelLifecycleCurriculum
import online.educoreng.educore.core.model.ParallelLifecycleEnrolment
import online.educoreng.educore.core.model.ParallelLifecycleGrade
import online.educoreng.educore.core.model.ParallelLifecyclePromotionRule
import online.educoreng.educore.core.model.ParallelLifecycleSession
import online.educoreng.educore.core.model.ParallelLifecycleStaff
import online.educoreng.educore.core.model.ParallelLifecycleSubjectAssignment
import online.educoreng.educore.core.model.ParallelLifecycleWorkspace
import online.educoreng.educore.core.model.ParallelPromotionHistory
import online.educoreng.educore.core.model.ParallelPromotionPreview
import online.educoreng.educore.core.model.ParallelPromotionPreviewCounts
import online.educoreng.educore.core.model.ParallelPromotionPreviewRow
import online.educoreng.educore.core.model.ParallelTransferHistory

data class ParallelLifecycleResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 2,
    @param:Json(name = "selected_curriculum_id") val selectedCurriculumId: Long? = null,
    @param:Json(name = "selected_session_id") val selectedSessionId: Long? = null,
    val curricula: List<ParallelLifecycleCurriculumDto> = emptyList(),
    val sessions: List<ParallelLifecycleSessionDto> = emptyList(),
    val staff: List<ParallelLifecycleStaffDto> = emptyList(),
    @param:Json(name = "arm_teacher_overrides_ready") val armTeacherOverridesReady: Boolean = false,
    val enrolments: List<ParallelLifecycleEnrolmentDto> = emptyList(),
    val transfers: List<ParallelTransferHistoryDto> = emptyList(),
    val promotions: List<ParallelPromotionHistoryDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String = "",
)

data class ParallelLifecycleSessionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class ParallelLifecycleStaffDto(
    val id: Long,
    val name: String,
)

data class ParallelLifecycleCurriculumDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    val classes: List<ParallelLifecycleClassDto> = emptyList(),
)

data class ParallelLifecycleClassDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    @param:Json(name = "is_active") val isActive: Boolean = true,
    val subjects: List<ParallelLifecycleSubjectAssignmentDto> = emptyList(),
    val arms: List<ParallelLifecycleArmDto> = emptyList(),
    @param:Json(name = "class_grades") val classGrades: List<ParallelLifecycleGradeDto> = emptyList(),
    @param:Json(name = "promotion_rule") val promotionRule: ParallelLifecyclePromotionRuleDto? = null,
)

data class ParallelLifecycleSubjectAssignmentDto(
    @param:Json(name = "assignment_id") val assignmentId: Long,
    @param:Json(name = "subject_id") val subjectId: Long,
    @param:Json(name = "subject_name") val subjectName: String? = null,
    @param:Json(name = "default_teacher_id") val defaultTeacherId: Long? = null,
    @param:Json(name = "default_teacher_name") val defaultTeacherName: String? = null,
)

data class ParallelLifecycleArmSubjectTeacherDto(
    @param:Json(name = "subject_id") val subjectId: Long,
    @param:Json(name = "teacher_id") val teacherId: Long,
    @param:Json(name = "teacher_name") val teacherName: String? = null,
)

data class ParallelLifecycleArmDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    val capacity: Int? = null,
    @param:Json(name = "is_active") val isActive: Boolean = true,
    @param:Json(name = "subject_teachers") val subjectTeachers: List<ParallelLifecycleArmSubjectTeacherDto> = emptyList(),
)

data class ParallelLifecycleGradeDto(
    val id: Long,
    @param:Json(name = "grade_letter") val gradeLetter: String,
    @param:Json(name = "min_score") val minScore: Double,
    @param:Json(name = "max_score") val maxScore: Double,
    val remark: String? = null,
    @param:Json(name = "is_pass_grade") val isPassGrade: Boolean = true,
    @param:Json(name = "grade_point") val gradePoint: Double? = null,
)

data class ParallelLifecyclePromotionRuleDto(
    val id: Long,
    @param:Json(name = "destination_class_id") val destinationClassId: Long? = null,
    @param:Json(name = "destination_class_name") val destinationClassName: String? = null,
    @param:Json(name = "minimum_average") val minimumAverage: Double,
    @param:Json(name = "max_failed_subjects") val maxFailedSubjects: Int,
    @param:Json(name = "require_complete_result") val requireCompleteResult: Boolean,
    @param:Json(name = "failure_action") val failureAction: String,
    @param:Json(name = "arm_strategy") val armStrategy: String,
    @param:Json(name = "is_terminal") val isTerminal: Boolean,
    @param:Json(name = "is_active") val isActive: Boolean,
)

data class ParallelLifecycleEnrolmentDto(
    val id: Long,
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "student_name") val studentName: String? = null,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class_id") val classId: Long,
    @param:Json(name = "class_name") val className: String? = null,
    @param:Json(name = "arm_id") val armId: Long? = null,
    @param:Json(name = "arm_name") val armName: String? = null,
)

data class ParallelTransferHistoryDto(
    val id: Long,
    @param:Json(name = "student_name") val studentName: String? = null,
    @param:Json(name = "movement_type") val movementType: String,
    @param:Json(name = "from_class") val fromClass: String? = null,
    @param:Json(name = "from_arm") val fromArm: String? = null,
    @param:Json(name = "to_class") val toClass: String? = null,
    @param:Json(name = "to_arm") val toArm: String? = null,
    val session: String? = null,
    val reason: String? = null,
    @param:Json(name = "effective_date") val effectiveDate: String? = null,
    @param:Json(name = "processed_at") val processedAt: String? = null,
)

data class ParallelPromotionHistoryDto(
    val id: Long,
    @param:Json(name = "student_name") val studentName: String? = null,
    val decision: String,
    @param:Json(name = "source_class") val sourceClass: String? = null,
    @param:Json(name = "source_arm") val sourceArm: String? = null,
    @param:Json(name = "destination_class") val destinationClass: String? = null,
    @param:Json(name = "destination_arm") val destinationArm: String? = null,
    @param:Json(name = "source_session") val sourceSession: String? = null,
    @param:Json(name = "target_session") val targetSession: String? = null,
    @param:Json(name = "average_score") val averageScore: Double? = null,
    @param:Json(name = "failed_subjects") val failedSubjects: Int = 0,
    val reason: String? = null,
    @param:Json(name = "processed_at") val processedAt: String? = null,
)

data class ParallelPromotionPreviewResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    @param:Json(name = "source_session") val sourceSession: ParallelLifecycleSessionDto,
    @param:Json(name = "target_session") val targetSession: ParallelLifecycleSessionDto,
    val counts: ParallelPromotionPreviewCountsDto,
    val rows: List<ParallelPromotionPreviewRowDto> = emptyList(),
)

data class ParallelPromotionPreviewCountsDto(
    val total: Int = 0,
    val promoted: Int = 0,
    val repeat: Int = 0,
    val retain: Int = 0,
    val graduated: Int = 0,
    val blocked: Int = 0,
)

data class ParallelPromotionPreviewRowDto(
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "student_name") val studentName: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "source_class") val sourceClass: String? = null,
    @param:Json(name = "source_arm") val sourceArm: String? = null,
    val average: Double? = null,
    @param:Json(name = "failed_subjects") val failedSubjects: Int = 0,
    val decision: String,
    @param:Json(name = "destination_class") val destinationClass: String? = null,
    @param:Json(name = "destination_arm") val destinationArm: String? = null,
    val reason: String,
)

data class ParallelPromotionRequestDto(
    @param:Json(name = "parallel_curriculum_id") val parallelCurriculumId: Long,
    @param:Json(name = "source_session_id") val sourceSessionId: Long,
    @param:Json(name = "target_session_id") val targetSessionId: Long,
)

data class ParallelTransferRequestDto(
    @param:Json(name = "enrolment_id") val enrolmentId: Long,
    @param:Json(name = "destination_class_id") val destinationClassId: Long,
    @param:Json(name = "destination_arm_id") val destinationArmId: Long,
    val reason: String,
    @param:Json(name = "effective_date") val effectiveDate: String? = null,
)

data class ParallelArmMutationRequestDto(
    @param:Json(name = "parallel_curriculum_class_id") val parallelCurriculumClassId: Long? = null,
    val name: String,
    val code: String? = null,
    val capacity: Int? = null,
)

data class ParallelArmTeacherMutationRequestDto(
    @param:Json(name = "parallel_curriculum_class_arm_id") val parallelCurriculumClassArmId: Long,
    @param:Json(name = "parallel_curriculum_subject_id") val parallelCurriculumSubjectId: Long,
    @param:Json(name = "teacher_id") val teacherId: Long? = null,
)

data class ParallelGradeMutationRequestDto(
    @param:Json(name = "parallel_curriculum_id") val parallelCurriculumId: Long,
    @param:Json(name = "class_ids") val classIds: List<Long>,
    @param:Json(name = "grade_letter") val gradeLetter: String,
    @param:Json(name = "min_score") val minScore: Double,
    @param:Json(name = "max_score") val maxScore: Double,
    val remark: String? = null,
    @param:Json(name = "is_pass_grade") val isPassGrade: Boolean = true,
    @param:Json(name = "grade_point") val gradePoint: Double? = null,
)

data class ParallelPromotionRuleMutationRequestDto(
    @param:Json(name = "parallel_curriculum_id") val parallelCurriculumId: Long,
    @param:Json(name = "source_class_id") val sourceClassId: Long,
    @param:Json(name = "destination_class_id") val destinationClassId: Long? = null,
    @param:Json(name = "minimum_average") val minimumAverage: Double,
    @param:Json(name = "max_failed_subjects") val maxFailedSubjects: Int,
    @param:Json(name = "require_complete_result") val requireCompleteResult: Boolean = true,
    @param:Json(name = "failure_action") val failureAction: String = "repeat",
    @param:Json(name = "arm_strategy") val armStrategy: String = "same_name",
    @param:Json(name = "is_terminal") val isTerminal: Boolean = false,
)

fun ParallelLifecycleResponseDto.toDomain(): ParallelLifecycleWorkspace =
    ParallelLifecycleWorkspace(
        selectedCurriculumId = selectedCurriculumId,
        selectedSessionId = selectedSessionId,
        curricula = curricula.map { it.toDomain() },
        sessions = sessions.map { it.toDomain() },
        staff = staff.map { it.toDomain() },
        armTeacherOverridesReady = armTeacherOverridesReady,
        enrolments = enrolments.map { it.toDomain() },
        transfers = transfers.map { it.toDomain() },
        promotions = promotions.map { it.toDomain() },
        generatedAt = generatedAt,
    )

private fun ParallelLifecycleSessionDto.toDomain() =
    ParallelLifecycleSession(id, name, isCurrent)

private fun ParallelLifecycleStaffDto.toDomain() =
    ParallelLifecycleStaff(id, name)

private fun ParallelLifecycleCurriculumDto.toDomain() =
    ParallelLifecycleCurriculum(id, name, code, classes.map { it.toDomain() })

private fun ParallelLifecycleClassDto.toDomain() =
    ParallelLifecycleClass(
        id = id,
        name = name,
        code = code,
        isActive = isActive,
        subjects = subjects.map { it.toDomain() },
        arms = arms.map { it.toDomain() },
        classGrades = classGrades.map { it.toDomain() },
        promotionRule = promotionRule?.toDomain(),
    )

private fun ParallelLifecycleSubjectAssignmentDto.toDomain() =
    ParallelLifecycleSubjectAssignment(
        assignmentId,
        subjectId,
        subjectName,
        defaultTeacherId,
        defaultTeacherName,
    )

private fun ParallelLifecycleArmSubjectTeacherDto.toDomain() =
    ParallelLifecycleArmSubjectTeacher(subjectId, teacherId, teacherName)

private fun ParallelLifecycleArmDto.toDomain() =
    ParallelLifecycleArm(
        id,
        name,
        code,
        capacity,
        isActive,
        subjectTeachers.map { it.toDomain() },
    )

private fun ParallelLifecycleGradeDto.toDomain() =
    ParallelLifecycleGrade(id, gradeLetter, minScore, maxScore, remark, isPassGrade, gradePoint)

private fun ParallelLifecyclePromotionRuleDto.toDomain() =
    ParallelLifecyclePromotionRule(
        id,
        destinationClassId,
        destinationClassName,
        minimumAverage,
        maxFailedSubjects,
        requireCompleteResult,
        failureAction,
        armStrategy,
        isTerminal,
        isActive,
    )

private fun ParallelLifecycleEnrolmentDto.toDomain() =
    ParallelLifecycleEnrolment(
        id,
        studentId,
        studentName,
        admissionNumber,
        classId,
        className,
        armId,
        armName,
    )

private fun ParallelTransferHistoryDto.toDomain() =
    ParallelTransferHistory(
        id,
        studentName,
        movementType,
        fromClass,
        fromArm,
        toClass,
        toArm,
        session,
        reason,
        effectiveDate,
        processedAt,
    )

private fun ParallelPromotionHistoryDto.toDomain() =
    ParallelPromotionHistory(
        id,
        studentName,
        decision,
        sourceClass,
        sourceArm,
        destinationClass,
        destinationArm,
        sourceSession,
        targetSession,
        averageScore,
        failedSubjects,
        reason,
        processedAt,
    )

fun ParallelPromotionPreviewResponseDto.toDomain(): ParallelPromotionPreview =
    ParallelPromotionPreview(
        sourceSession = sourceSession.toDomain(),
        targetSession = targetSession.toDomain(),
        counts = ParallelPromotionPreviewCounts(
            total = counts.total,
            promoted = counts.promoted,
            repeat = counts.repeat,
            retain = counts.retain,
            graduated = counts.graduated,
            blocked = counts.blocked,
        ),
        rows = rows.map {
            ParallelPromotionPreviewRow(
                studentId = it.studentId,
                studentName = it.studentName,
                admissionNumber = it.admissionNumber,
                sourceClass = it.sourceClass,
                sourceArm = it.sourceArm,
                average = it.average,
                failedSubjects = it.failedSubjects,
                decision = it.decision,
                destinationClass = it.destinationClass,
                destinationArm = it.destinationArm,
                reason = it.reason,
            )
        },
    )
