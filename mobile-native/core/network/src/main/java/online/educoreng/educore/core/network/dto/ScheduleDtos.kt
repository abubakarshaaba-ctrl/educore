package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.ExamDuty
import online.educoreng.educore.core.model.ScheduleDay
import online.educoreng.educore.core.model.SchedulePeriod
import online.educoreng.educore.core.model.ScheduleWorkspace
import online.educoreng.educore.core.model.ScheduledExam

data class ScheduleRangeDto(val from: String, val to: String)
data class ScheduleScopeDto(
    val type: String,
    val title: String,
    @param:Json(name = "class") val classRoom: ScoreClassDto? = null,
)
data class SchedulePeriodDto(
    val id: Long,
    @param:Json(name = "start_time") val startTime: String? = null,
    @param:Json(name = "end_time") val endTime: String? = null,
    val subject: String,
    @param:Json(name = "class") val className: String? = null,
    val teacher: String? = null,
    val venue: String? = null,
)
data class ScheduleDayDto(val day: String, val periods: List<SchedulePeriodDto> = emptyList())
data class ScheduledExamDto(
    val id: Long,
    val title: String,
    val date: String,
    val session: String? = null,
    @param:Json(name = "start_time") val startTime: String? = null,
    @param:Json(name = "end_time") val endTime: String? = null,
    val subject: String,
    @param:Json(name = "class_level") val classLevel: String? = null,
    val venue: String? = null,
)
data class ExamDutyDto(
    val id: Long,
    @param:Json(name = "exam_title") val examTitle: String,
    val date: String? = null,
    val session: String? = null,
    @param:Json(name = "start_time") val startTime: String? = null,
    @param:Json(name = "end_time") val endTime: String? = null,
    val subject: String,
    @param:Json(name = "class_level") val classLevel: String? = null,
    val venue: String? = null,
)
data class ScheduleResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val range: ScheduleRangeDto,
    val session: ScoreTermDto? = null,
    val term: ScoreTermDto? = null,
    val scope: ScheduleScopeDto,
    val week: List<ScheduleDayDto> = emptyList(),
    val exams: List<ScheduledExamDto> = emptyList(),
    val duties: List<ExamDutyDto> = emptyList(),
)

fun ScheduleResponseDto.toDomain(fromCache: Boolean = false) = ScheduleWorkspace(
    scopeType = scope.type,
    title = scope.title,
    classId = scope.classRoom?.id,
    className = scope.classRoom?.name,
    sessionName = session?.name ?: term?.session,
    termName = term?.name,
    from = range.from,
    to = range.to,
    week = week.map { day ->
        ScheduleDay(day.day, day.periods.map {
            SchedulePeriod(it.id, it.startTime, it.endTime, it.subject, it.className, it.teacher, it.venue)
        })
    },
    exams = exams.map {
        ScheduledExam(it.id, it.title, it.date, it.session, it.startTime, it.endTime, it.subject, it.classLevel, it.venue)
    },
    duties = duties.map {
        ExamDuty(it.id, it.examTitle, it.date, it.session, it.startTime, it.endTime, it.subject, it.classLevel, it.venue)
    },
    generatedAt = generatedAt,
    isFromCache = fromCache,
)
