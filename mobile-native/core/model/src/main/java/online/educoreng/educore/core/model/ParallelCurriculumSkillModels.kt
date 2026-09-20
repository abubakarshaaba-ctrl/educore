package online.educoreng.educore.core.model

data class ParallelSkillWorkspace(
    val selectedArmId: Long?,
    val selectedTermId: Long?,
    val arms: List<ParallelSkillArm>,
    val terms: List<ParallelSkillTerm>,
    val skills: List<ParallelSkillDefinition>,
    val students: List<ParallelSkillStudent>,
    val ratingScale: List<ParallelSkillScale>,
    val canManage: Boolean,
)

data class ParallelSkillArm(
    val id: Long,
    val name: String,
    val classId: Long,
    val className: String?,
    val curriculumId: Long,
    val curriculumName: String?,
    val label: String,
)

data class ParallelSkillTerm(
    val id: Long,
    val name: String,
    val sessionId: Long,
    val sessionName: String?,
    val isCurrent: Boolean,
    val label: String,
)

data class ParallelSkillDefinition(
    val id: Long,
    val name: String,
    val category: String,
    val sortOrder: Int,
)

data class ParallelSkillStudent(
    val enrolmentId: Long,
    val studentId: Long,
    val name: String,
    val admissionNumber: String?,
    val ratings: Map<Long, Int>,
)

data class ParallelSkillScale(
    val value: Int,
    val label: String,
)

data class ParallelSkillStudentDraft(
    val enrolmentId: Long,
    val ratings: Map<Long, Int?>,
)
