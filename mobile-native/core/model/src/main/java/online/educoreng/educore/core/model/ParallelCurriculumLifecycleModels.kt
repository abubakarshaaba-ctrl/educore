package online.educoreng.educore.core.model

data class ParallelLifecycleSession(
    val id: Long,
    val name: String,
    val isCurrent: Boolean,
)

data class ParallelLifecycleArmSubjectTeacher(
    val subjectId: Long,
    val teacherId: Long,
    val teacherName: String?,
)

data class ParallelLifecycleArm(
    val id: Long,
    val name: String,
    val code: String?,
    val capacity: Int?,
    val isActive: Boolean,
    val subjectTeachers: List<ParallelLifecycleArmSubjectTeacher> = emptyList(),
)

data class ParallelLifecycleGrade(
    val id: Long,
    val gradeLetter: String,
    val minScore: Double,
    val maxScore: Double,
    val remark: String?,
    val isPassGrade: Boolean,
    val gradePoint: Double?,
)

data class ParallelLifecycleSubjectAssignment(
    val assignmentId: Long,
    val subjectId: Long,
    val subjectName: String?,
    val defaultTeacherId: Long?,
    val defaultTeacherName: String?,
)

data class ParallelLifecycleStaff(
    val id: Long,
    val name: String,
)

data class ParallelLifecyclePromotionRule(
    val id: Long,
    val destinationClassId: Long?,
    val destinationClassName: String?,
    val minimumAverage: Double,
    val maxFailedSubjects: Int,
    val requireCompleteResult: Boolean,
    val failureAction: String,
    val armStrategy: String,
    val isTerminal: Boolean,
    val isActive: Boolean,
)

data class ParallelLifecycleClass(
    val id: Long,
    val name: String,
    val code: String?,
    val isActive: Boolean,
    val subjects: List<ParallelLifecycleSubjectAssignment>,
    val arms: List<ParallelLifecycleArm>,
    val classGrades: List<ParallelLifecycleGrade>,
    val promotionRule: ParallelLifecyclePromotionRule?,
)

data class ParallelLifecycleCurriculum(
    val id: Long,
    val name: String,
    val code: String?,
    val classes: List<ParallelLifecycleClass>,
)

data class ParallelLifecycleEnrolment(
    val id: Long,
    val studentId: Long,
    val studentName: String?,
    val admissionNumber: String?,
    val classId: Long,
    val className: String?,
    val armId: Long?,
    val armName: String?,
)

data class ParallelTransferHistory(
    val id: Long,
    val studentName: String?,
    val movementType: String,
    val fromClass: String?,
    val fromArm: String?,
    val toClass: String?,
    val toArm: String?,
    val session: String?,
    val reason: String?,
    val effectiveDate: String?,
    val processedAt: String?,
)

data class ParallelPromotionHistory(
    val id: Long,
    val studentName: String?,
    val decision: String,
    val sourceClass: String?,
    val sourceArm: String?,
    val destinationClass: String?,
    val destinationArm: String?,
    val sourceSession: String?,
    val targetSession: String?,
    val averageScore: Double?,
    val failedSubjects: Int,
    val reason: String?,
    val processedAt: String?,
)

data class ParallelLifecycleWorkspace(
    val selectedCurriculumId: Long?,
    val selectedSessionId: Long?,
    val curricula: List<ParallelLifecycleCurriculum>,
    val sessions: List<ParallelLifecycleSession>,
    val staff: List<ParallelLifecycleStaff>,
    val armTeacherOverridesReady: Boolean,
    val enrolments: List<ParallelLifecycleEnrolment>,
    val transfers: List<ParallelTransferHistory>,
    val promotions: List<ParallelPromotionHistory>,
    val generatedAt: String,
) {
    val selectedCurriculum: ParallelLifecycleCurriculum?
        get() = curricula.firstOrNull { it.id == selectedCurriculumId }

    val selectedSession: ParallelLifecycleSession?
        get() = sessions.firstOrNull { it.id == selectedSessionId }
}

data class ParallelPromotionPreviewCounts(
    val total: Int,
    val promoted: Int,
    val repeat: Int,
    val retain: Int,
    val graduated: Int,
    val blocked: Int,
)

data class ParallelPromotionPreviewRow(
    val studentId: Long,
    val studentName: String,
    val admissionNumber: String?,
    val sourceClass: String?,
    val sourceArm: String?,
    val average: Double?,
    val failedSubjects: Int,
    val decision: String,
    val destinationClass: String?,
    val destinationArm: String?,
    val reason: String,
)

data class ParallelPromotionPreview(
    val sourceSession: ParallelLifecycleSession,
    val targetSession: ParallelLifecycleSession,
    val counts: ParallelPromotionPreviewCounts,
    val rows: List<ParallelPromotionPreviewRow>,
)
