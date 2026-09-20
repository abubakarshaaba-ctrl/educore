package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.CbtAttempt
import online.educoreng.educore.core.model.CbtExam
import online.educoreng.educore.core.model.CbtIntegrityResult
import online.educoreng.educore.core.model.CbtIntegrityNotice
import online.educoreng.educore.core.model.CbtPreflight
import online.educoreng.educore.core.model.CbtQuestion
import online.educoreng.educore.core.model.CbtResult
import online.educoreng.educore.core.model.CbtSection
import online.educoreng.educore.core.model.CbtSectionSummary
import online.educoreng.educore.core.model.CbtSession

data class CbtSectionSummaryDto(
    val id: Long,
    val code: String,
    val name: String,
    @param:Json(name = "answer_mode") val answerMode: String,
    @param:Json(name = "max_marks") val maxMarks: Double,
)

data class CbtExamDto(
    val id: Long,
    val title: String,
    val subject: String? = null,
    @param:Json(name = "duration_minutes") val durationMinutes: Int,
    @param:Json(name = "total_questions") val totalQuestions: Int,
    @param:Json(name = "total_marks") val totalMarks: Double,
    @param:Json(name = "scheduled_start") val scheduledStart: String? = null,
    @param:Json(name = "scheduled_end") val scheduledEnd: String? = null,
    val status: String,
    @param:Json(name = "malpractice_enabled") val malpracticeEnabled: Boolean = false,
    @param:Json(name = "require_fullscreen") val requireFullscreen: Boolean = false,
    @param:Json(name = "focus_loss_policy") val focusLossPolicy: String? = null,
    @param:Json(name = "max_focus_losses") val maxFocusLosses: Int = 0,
    val sections: List<CbtSectionSummaryDto> = emptyList(),
)

data class CbtResultDto(
    @param:Json(name = "session_id") val sessionId: Long,
    val status: String,
    @param:Json(name = "attempt_number") val attemptNumber: Int,
    @param:Json(name = "submitted_at") val submittedAt: String? = null,
    @param:Json(name = "fully_scored") val fullyScored: Boolean,
    val score: Double? = null,
    @param:Json(name = "maximum_score") val maximumScore: Double? = null,
    val percentage: Double? = null,
)

data class CbtPreflightDto(
    val exam: CbtExamDto,
    @param:Json(name = "active_session_id") val activeSessionId: Long? = null,
    @param:Json(name = "latest_attempt") val latestAttempt: CbtResultDto? = null,
    @param:Json(name = "window_state") val windowState: String,
    @param:Json(name = "can_begin") val canBegin: Boolean,
    @param:Json(name = "can_resume") val canResume: Boolean,
    @param:Json(name = "retake_authorized") val retakeAuthorized: Boolean,
    @param:Json(name = "integrity_notice") val integrityNotice: CbtIntegrityNoticeDto,
)

data class CbtExamsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val exams: List<CbtPreflightDto> = emptyList(),
)

data class CbtPreflightResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val exam: CbtExamDto,
    @param:Json(name = "active_session_id") val activeSessionId: Long? = null,
    @param:Json(name = "latest_attempt") val latestAttempt: CbtResultDto? = null,
    @param:Json(name = "window_state") val windowState: String,
    @param:Json(name = "can_begin") val canBegin: Boolean,
    @param:Json(name = "can_resume") val canResume: Boolean,
    @param:Json(name = "retake_authorized") val retakeAuthorized: Boolean,
    @param:Json(name = "integrity_notice") val integrityNotice: CbtIntegrityNoticeDto,
)

data class CbtIntegrityNoticeDto(
    @param:Json(name = "secure_screen_required") val secureScreenRequired: Boolean,
    @param:Json(name = "fullscreen_required") val fullscreenRequired: Boolean,
    @param:Json(name = "focus_loss_policy") val focusLossPolicy: String,
    @param:Json(name = "max_focus_losses") val maxFocusLosses: Int,
    val message: String,
)

data class CbtQuestionDto(
    val id: Long,
    @param:Json(name = "parent_id") val parentId: Long? = null,
    @param:Json(name = "display_path") val displayPath: String,
    val level: Int,
    val type: String,
    val text: String,
    val html: String? = null,
    val options: Map<String, String> = emptyMap(),
    val marks: Double,
    @param:Json(name = "requires_answer") val requiresAnswer: Boolean,
    @param:Json(name = "instruction_only") val instructionOnly: Boolean,
    @param:Json(name = "has_image") val hasImage: Boolean,
)

data class CbtSectionDto(
    val id: Long,
    val code: String,
    val name: String,
    val title: String? = null,
    val instructions: String? = null,
    @param:Json(name = "answer_mode") val answerMode: String,
    @param:Json(name = "section_type") val sectionType: String,
    @param:Json(name = "max_marks") val maxMarks: Double,
    val questions: List<CbtQuestionDto> = emptyList(),
)

data class CbtSessionDto(
    val id: Long,
    @param:Json(name = "exam_id") val examId: Long,
    val status: String,
    @param:Json(name = "attempt_number") val attemptNumber: Int,
    val version: String,
    @param:Json(name = "deadline_at") val deadlineAt: String? = null,
    @param:Json(name = "remaining_seconds") val remainingSeconds: Int,
    @param:Json(name = "server_time") val serverTime: String,
    val answers: Map<String, String> = emptyMap(),
    @param:Json(name = "flagged_questions") val flaggedQuestions: List<Long> = emptyList(),
    @param:Json(name = "focus_loss_count") val focusLossCount: Int = 0,
)

data class CbtAttemptResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val session: CbtSessionDto,
    val sections: List<CbtSectionDto> = emptyList(),
    val result: CbtResultDto? = null,
)

data class CbtBeginRequestDto(
    @param:Json(name = "request_id") val requestId: String,
    @param:Json(name = "integrity_acknowledged") val integrityAcknowledged: Boolean = true,
)

data class CbtSaveRequestDto(
    @param:Json(name = "request_id") val requestId: String,
    val version: String,
    val answers: Map<String, String>,
    @param:Json(name = "flagged_questions") val flaggedQuestions: List<Long>,
)

data class CbtSubmitRequestDto(
    @param:Json(name = "request_id") val requestId: String,
    val answers: Map<String, String>,
)

data class CbtIntegrityRequestDto(
    @param:Json(name = "event_uuid") val eventUuid: String,
    @param:Json(name = "event_type") val eventType: String,
    val metadata: Map<String, String> = emptyMap(),
    val answers: Map<String, String> = emptyMap(),
)

data class CbtIntegrityResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val recorded: Boolean,
    val submitted: Boolean,
    val status: String,
    @param:Json(name = "focus_loss_count") val focusLossCount: Int,
)

fun CbtExamDto.toDomain() = CbtExam(
    id, title, subject, durationMinutes, totalQuestions, totalMarks, scheduledStart, scheduledEnd,
    malpracticeEnabled, requireFullscreen, focusLossPolicy, maxFocusLosses,
    sections.map { CbtSectionSummary(it.id, it.code, it.name, it.answerMode, it.maxMarks) },
)

fun CbtResultDto.toDomain() = CbtResult(sessionId, status, attemptNumber, submittedAt, fullyScored, score, maximumScore, percentage)

fun CbtPreflightDto.toDomain() = CbtPreflight(
    exam.toDomain(), activeSessionId, latestAttempt?.toDomain(), windowState, canBegin, canResume,
    retakeAuthorized, integrityNotice.toDomain(),
)

fun CbtPreflightResponseDto.toDomain() = CbtPreflight(
    exam.toDomain(), activeSessionId, latestAttempt?.toDomain(), windowState, canBegin, canResume,
    retakeAuthorized, integrityNotice.toDomain(),
)

fun CbtIntegrityNoticeDto.toDomain() = CbtIntegrityNotice(
    secureScreenRequired, fullscreenRequired, focusLossPolicy, maxFocusLosses, message,
)

fun CbtAttemptResponseDto.toDomain() = CbtAttempt(
    session = CbtSession(
        session.id, session.examId, session.status, session.attemptNumber, session.version,
        session.deadlineAt, session.remainingSeconds, session.serverTime,
        session.answers.mapNotNull { (key, value) -> key.toLongOrNull()?.let { it to value } }.toMap(),
        session.flaggedQuestions.toSet(), session.focusLossCount,
    ),
    sections = sections.map { section ->
        CbtSection(
            section.id, section.code, section.name, section.title, section.instructions, section.answerMode,
            section.sectionType, section.maxMarks,
            section.questions.map { question ->
                CbtQuestion(
                    question.id, question.parentId, question.displayPath, question.level, question.type,
                    question.text, question.html, question.options, question.marks, question.requiresAnswer,
                    question.instructionOnly, question.hasImage,
                )
            },
        )
    },
    result = result?.toDomain(),
)

fun CbtIntegrityResponseDto.toDomain() = CbtIntegrityResult(recorded, submitted, status, focusLossCount)
