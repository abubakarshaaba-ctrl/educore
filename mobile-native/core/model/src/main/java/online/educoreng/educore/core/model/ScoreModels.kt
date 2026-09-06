package online.educoreng.educore.core.model

enum class SyncState { NONE, QUEUED, SYNCING, FAILED, CONFLICT }

data class ScoreAssignment(
    val classId: Long,
    val className: String,
    val subjectId: Long,
    val subjectName: String,
)

data class ScoreAssignments(
    val termId: Long?,
    val termName: String?,
    val sessionName: String?,
    val assignments: List<ScoreAssignment>,
    val generatedAt: String,
    val isFromCache: Boolean = false,
)

data class ScoreAssessment(
    val id: Long,
    val name: String,
    val maximum: Double,
    val isExam: Boolean,
    val isSplit: Boolean,
    val objectiveMaximum: Double?,
    val theoryMaximum: Double?,
    val objectiveSourceAvailable: Boolean?,
)

data class ScoreCell(
    val assessmentId: Long,
    val total: Double?,
    val value: Double?,
    val objectiveScore: Double?,
    val theoryScore: Double?,
    val locked: Boolean,
    val source: String?,
)

data class ScoreStudent(
    val id: Long,
    val name: String,
    val admissionNumber: String,
    val scores: Map<Long, ScoreCell>,
)

data class ScoreSheet(
    val classId: Long,
    val className: String,
    val subjectId: Long,
    val subjectName: String,
    val termId: Long,
    val termName: String,
    val sessionName: String?,
    val version: String,
    val locked: Boolean,
    val lockReason: String?,
    val assessments: List<ScoreAssessment>,
    val students: List<ScoreStudent>,
    val generatedAt: String,
    val hasLocalDraft: Boolean = false,
    val isDraftStale: Boolean = false,
    val syncState: SyncState = SyncState.NONE,
    val syncMessage: String? = null,
)

data class ResultAssessment(
    val name: String,
    val score: Double?,
    val maximum: Double,
)

data class ResultSubject(
    val name: String,
    val assessments: List<ResultAssessment>,
    val total: Double,
    val grade: String,
    val remark: String,
)

data class PublishedResult(
    val id: Long,
    val term: String?,
    val session: String?,
    val average: Double,
    val totalScore: Double,
    val position: Int?,
    val classSize: Int?,
    val subjectsOffered: Int,
    val subjectsFailed: Int,
    val promotionStatus: String,
    val formTutorRemark: String?,
    val principalRemark: String?,
    val subjects: List<ResultSubject>,
)

data class PublishedResults(
    val studentName: String?,
    val admissionNumber: String?,
    val className: String?,
    val results: List<PublishedResult>,
)
