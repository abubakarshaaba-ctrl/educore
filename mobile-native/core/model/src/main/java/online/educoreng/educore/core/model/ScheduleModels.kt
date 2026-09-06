package online.educoreng.educore.core.model

data class SchedulePeriod(
    val id: Long,
    val startTime: String?,
    val endTime: String?,
    val subject: String,
    val className: String?,
    val teacher: String?,
    val venue: String?,
)

data class ScheduleDay(val day: String, val periods: List<SchedulePeriod>)

data class ScheduledExam(
    val id: Long,
    val title: String,
    val date: String,
    val session: String?,
    val startTime: String?,
    val endTime: String?,
    val subject: String,
    val classLevel: String?,
    val venue: String?,
)

data class ExamDuty(
    val id: Long,
    val examTitle: String,
    val date: String?,
    val session: String?,
    val startTime: String?,
    val endTime: String?,
    val subject: String,
    val classLevel: String?,
    val venue: String?,
)

data class ScheduleWorkspace(
    val scopeType: String,
    val title: String,
    val classId: Long?,
    val className: String?,
    val sessionName: String?,
    val termName: String?,
    val from: String,
    val to: String,
    val week: List<ScheduleDay>,
    val exams: List<ScheduledExam>,
    val duties: List<ExamDuty>,
    val generatedAt: String,
    val isFromCache: Boolean = false,
)
