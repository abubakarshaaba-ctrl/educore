package online.educoreng.educore.core.model

data class CbtExam(
    val id: Long,
    val title: String,
    val subject: String?,
    val durationMinutes: Int,
    val totalQuestions: Int,
    val totalMarks: Double,
    val scheduledStart: String?,
    val scheduledEnd: String?,
    val malpracticeEnabled: Boolean,
    val requireFullscreen: Boolean,
    val focusLossPolicy: String?,
    val maxFocusLosses: Int,
    val sections: List<CbtSectionSummary>,
)

data class CbtSectionSummary(
    val id: Long,
    val code: String,
    val name: String,
    val answerMode: String,
    val maxMarks: Double,
)

data class CbtPreflight(
    val exam: CbtExam,
    val activeSessionId: Long?,
    val latestAttempt: CbtResult?,
    val windowState: String,
    val canBegin: Boolean,
    val canResume: Boolean,
    val retakeAuthorized: Boolean,
    val integrityNotice: CbtIntegrityNotice,
)

data class CbtIntegrityNotice(
    val secureScreenRequired: Boolean,
    val fullscreenRequired: Boolean,
    val focusLossPolicy: String,
    val maxFocusLosses: Int,
    val message: String,
)

data class CbtAttempt(
    val session: CbtSession,
    val sections: List<CbtSection>,
    val result: CbtResult?,
)

data class CbtSession(
    val id: Long,
    val examId: Long,
    val status: String,
    val attemptNumber: Int,
    val version: String,
    val deadlineAt: String?,
    val remainingSeconds: Int,
    val serverTime: String,
    val answers: Map<Long, String>,
    val flaggedQuestions: Set<Long>,
    val focusLossCount: Int,
)

data class CbtSection(
    val id: Long,
    val code: String,
    val name: String,
    val title: String?,
    val instructions: String?,
    val answerMode: String,
    val sectionType: String,
    val maxMarks: Double,
    val questions: List<CbtQuestion>,
)

data class CbtQuestion(
    val id: Long,
    val parentId: Long?,
    val displayPath: String,
    val level: Int,
    val type: String,
    val text: String,
    val html: String?,
    val options: Map<String, String>,
    val marks: Double,
    val requiresAnswer: Boolean,
    val instructionOnly: Boolean,
    val hasImage: Boolean,
)

data class CbtResult(
    val sessionId: Long,
    val status: String,
    val attemptNumber: Int,
    val submittedAt: String?,
    val fullyScored: Boolean,
    val score: Double?,
    val maximumScore: Double?,
    val percentage: Double?,
)

data class CbtIntegrityResult(
    val recorded: Boolean,
    val submitted: Boolean,
    val status: String,
    val focusLossCount: Int,
)
