package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.PublishedResult
import online.educoreng.educore.core.model.PublishedResults
import online.educoreng.educore.core.model.ResultAssessment
import online.educoreng.educore.core.model.ResultSubject
import online.educoreng.educore.core.model.ScoreAssessment
import online.educoreng.educore.core.model.ScoreAssignment
import online.educoreng.educore.core.model.ScoreAssignments
import online.educoreng.educore.core.model.ScoreCell
import online.educoreng.educore.core.model.ScoreSheet
import online.educoreng.educore.core.model.ScoreStudent

data class ScoreTermDto(val id: Long, val name: String, val session: String? = null)
data class ScoreClassDto(val id: Long, val name: String)
data class ScoreSubjectDto(val id: Long, val name: String)

data class ScoreAssignmentDto(
    @param:Json(name = "class_arm_id") val classId: Long,
    @param:Json(name = "class_name") val className: String,
    @param:Json(name = "subject_id") val subjectId: Long,
    @param:Json(name = "subject_name") val subjectName: String,
)

data class ScoreAssignmentsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val term: ScoreTermDto? = null,
    val assignments: List<ScoreAssignmentDto> = emptyList(),
)

data class ScoreAssessmentDto(
    val id: Long,
    val name: String,
    val max: Double,
    @param:Json(name = "is_exam") val isExam: Boolean,
    @param:Json(name = "is_split") val isSplit: Boolean,
    @param:Json(name = "objective_max") val objectiveMaximum: Double? = null,
    @param:Json(name = "theory_max") val theoryMaximum: Double? = null,
    @param:Json(name = "objective_source_available") val objectiveSourceAvailable: Boolean? = null,
)

data class ScoreCellDto(
    val total: Double? = null,
    val value: Double? = null,
    @param:Json(name = "objective_score") val objectiveScore: Double? = null,
    @param:Json(name = "theory_score") val theoryScore: Double? = null,
    val locked: Boolean = false,
    val source: String? = null,
)

data class ScoreStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String,
    val scores: Map<String, ScoreCellDto> = emptyMap(),
)

data class ScoreSheetResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val version: String,
    val locked: Boolean,
    @param:Json(name = "lock_reason") val lockReason: String? = null,
    @param:Json(name = "class") val classRoom: ScoreClassDto,
    val subject: ScoreSubjectDto,
    val term: ScoreTermDto,
    @param:Json(name = "assessment_types") val assessmentTypes: List<ScoreAssessmentDto> = emptyList(),
    val students: List<ScoreStudentDto> = emptyList(),
)

data class SaveScoresRequestDto(
    @param:Json(name = "class_arm_id") val classId: Long,
    @param:Json(name = "subject_id") val subjectId: Long,
    @param:Json(name = "term_id") val termId: Long,
    val version: String,
    @param:Json(name = "request_id") val requestId: String,
    val scores: Map<String, Map<String, Double?>>,
)

data class SaveScoresResponseDto(
    val message: String,
    val saved: Int,
    @param:Json(name = "request_id") val requestId: String,
    val version: String,
    @param:Json(name = "saved_at") val savedAt: String,
)

data class ResultStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String,
    @param:Json(name = "class") val classRoom: ScoreClassDto? = null,
)

data class ResultAssessmentDto(val name: String, val score: Double? = null, val maximum: Double)
data class ResultSubjectDto(
    @param:Json(name = "subject") val name: String,
    val assessments: List<ResultAssessmentDto> = emptyList(),
    val total: Double,
    val grade: String,
    val remark: String,
)

data class PublishedResultDto(
    val id: Long,
    val term: String? = null,
    val session: String? = null,
    val average: Double,
    @param:Json(name = "total_score") val totalScore: Double,
    val position: Int? = null,
    @param:Json(name = "class_size") val classSize: Int? = null,
    @param:Json(name = "subjects_offered") val subjectsOffered: Int,
    @param:Json(name = "subjects_failed") val subjectsFailed: Int,
    @param:Json(name = "promotion_status") val promotionStatus: String,
    @param:Json(name = "form_tutor_remark") val formTutorRemark: String? = null,
    @param:Json(name = "principal_remark") val principalRemark: String? = null,
    val subjects: List<ResultSubjectDto> = emptyList(),
)

data class PublishedResultsResponseDto(
    val student: ResultStudentDto? = null,
    val results: List<PublishedResultDto> = emptyList(),
)

fun ScoreAssignmentsResponseDto.toDomain(fromCache: Boolean = false) = ScoreAssignments(
    termId = term?.id, termName = term?.name, sessionName = term?.session,
    assignments = assignments.map { ScoreAssignment(it.classId, it.className, it.subjectId, it.subjectName) },
    generatedAt = generatedAt, isFromCache = fromCache,
)

fun ScoreSheetResponseDto.toDomain(drafts: Map<Pair<Long, Long>, Double?> = emptyMap(), stale: Boolean = false) = ScoreSheet(
    classId = classRoom.id, className = classRoom.name, subjectId = subject.id, subjectName = subject.name,
    termId = term.id, termName = term.name, sessionName = term.session, version = version,
    locked = locked, lockReason = lockReason,
    assessments = assessmentTypes.map {
        ScoreAssessment(it.id, it.name, it.max, it.isExam, it.isSplit, it.objectiveMaximum, it.theoryMaximum, it.objectiveSourceAvailable)
    },
    students = students.map { student ->
        ScoreStudent(student.id, student.name, student.admissionNumber, student.scores.mapNotNull { (key, value) ->
            val id = key.toLongOrNull() ?: return@mapNotNull null
            val draftKey = student.id to id
            id to ScoreCell(id, value.total, if (drafts.containsKey(draftKey)) drafts[draftKey] else value.value, value.objectiveScore, value.theoryScore, value.locked, value.source)
        }.toMap())
    },
    generatedAt = generatedAt, hasLocalDraft = drafts.isNotEmpty(), isDraftStale = stale,
)

fun PublishedResultsResponseDto.toDomain() = PublishedResults(
    studentName = student?.name, admissionNumber = student?.admissionNumber, className = student?.classRoom?.name,
    results = results.map { result ->
        PublishedResult(
            result.id, result.term, result.session, result.average, result.totalScore, result.position, result.classSize,
            result.subjectsOffered, result.subjectsFailed, result.promotionStatus, result.formTutorRemark, result.principalRemark,
            result.subjects.map { subject ->
                ResultSubject(subject.name, subject.assessments.map { ResultAssessment(it.name, it.score, it.maximum) }, subject.total, subject.grade, subject.remark)
            },
        )
    },
)
