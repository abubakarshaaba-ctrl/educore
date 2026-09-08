package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class StaffCbtCapabilitiesDto(
    @Json(name = "full_access") val fullAccess: Boolean = false,
    @Json(name = "create_exam") val createExam: Boolean = false,
    @Json(name = "publish_exam") val publishExam: Boolean = false,
    @Json(name = "close_exam") val closeExam: Boolean = false,
    @Json(name = "reschedule_exam") val rescheduleExam: Boolean = false,
)

data class StaffCbtCountsDto(
    val all: Int = 0,
    val draft: Int = 0,
    val published: Int = 0,
    val closed: Int = 0,
)

data class StaffCbtIdentityDto(
    val id: Long,
    val name: String,
)

data class StaffCbtTermDto(
    val id: Long,
    val name: String,
    val session: String? = null,
)

data class StaffCbtAttemptsDto(
    val total: Int = 0,
    val submitted: Int = 0,
    val graded: Int = 0,
)

data class StaffCbtSectionDto(
    val id: Long,
    val title: String,
    @Json(name = "display_order") val displayOrder: Int = 0,
)

data class StaffCbtExamDto(
    val id: Long,
    val title: String,
    val status: String,
    @Json(name = "duration_minutes") val durationMinutes: Int = 0,
    @Json(name = "total_questions") val totalQuestions: Int = 0,
    @Json(name = "total_marks") val totalMarks: Double = 0.0,
    @Json(name = "scheduled_start") val scheduledStart: String? = null,
    @Json(name = "scheduled_end") val scheduledEnd: String? = null,
    val subject: StaffCbtIdentityDto? = null,
    @Json(name = "class_level") val classLevel: StaffCbtIdentityDto? = null,
    val classes: List<StaffCbtIdentityDto> = emptyList(),
    val term: StaffCbtTermDto? = null,
    @Json(name = "attempts_count") val attemptsCount: Int = 0,
    @Json(name = "can_publish") val canPublish: Boolean = false,
    @Json(name = "can_close") val canClose: Boolean = false,
    @Json(name = "can_reschedule") val canReschedule: Boolean = false,
    val sections: List<StaffCbtSectionDto> = emptyList(),
    val attempts: StaffCbtAttemptsDto? = null,
)

data class StaffCbtExamsResponseDto(
    @Json(name = "contract_version") val contractVersion: Int,
    @Json(name = "generated_at") val generatedAt: String,
    val capabilities: StaffCbtCapabilitiesDto = StaffCbtCapabilitiesDto(),
    val counts: StaffCbtCountsDto = StaffCbtCountsDto(),
    val exams: List<StaffCbtExamDto> = emptyList(),
)

data class StaffCbtExamResponseDto(
    @Json(name = "contract_version") val contractVersion: Int,
    @Json(name = "generated_at") val generatedAt: String,
    val exam: StaffCbtExamDto,
)

data class StaffCbtMutationResponseDto(
    val message: String,
    val exam: StaffCbtExamDto,
)

data class StaffCbtRescheduleRequestDto(
    @Json(name = "scheduled_start") val scheduledStart: String,
    @Json(name = "scheduled_end") val scheduledEnd: String,
    @Json(name = "duration_minutes") val durationMinutes: Int,
)
