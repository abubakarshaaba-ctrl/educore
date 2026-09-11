package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PaginationDto(
    @param:Json(name = "current_page") val currentPage: Int = 1,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 0,
    val total: Int = 0,
)

data class ClassIdentityDto(val id: Long, val name: String)

data class ClassCapabilitiesDto(
    @param:Json(name = "view_students") val viewStudents: Boolean = false,
    @param:Json(name = "mark_attendance") val markAttendance: Boolean = false,
    @param:Json(name = "enter_scores") val enterScores: Boolean = false,
    @param:Json(name = "view_results") val viewResults: Boolean = false,
    @param:Json(name = "plan_lessons") val planLessons: Boolean = false,
)

data class ClassSubjectDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    @param:Json(name = "teacher_id") val teacherId: Long? = null,
    @param:Json(name = "teacher_name") val teacherName: String? = null,
)

data class ClassSummaryDto(
    val id: Long,
    val name: String,
    val level: ClassIdentityDto? = null,
    val track: ClassIdentityDto? = null,
    @param:Json(name = "form_tutor") val formTutor: ClassIdentityDto? = null,
    val roles: List<String> = emptyList(),
    val subjects: List<ClassSubjectDto> = emptyList(),
    @param:Json(name = "students_count") val studentsCount: Int = 0,
    val capabilities: ClassCapabilitiesDto = ClassCapabilitiesDto(),
)

data class ClassListResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val classes: List<ClassSummaryDto> = emptyList(),
    val meta: PaginationDto = PaginationDto(),
)

data class ClassDetailResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    @param:Json(name = "class") val classSummary: ClassSummaryDto,
)

data class StudentSummaryDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String,
    val gender: String? = null,
    val initials: String = "",
)

data class ClassStudentsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    @param:Json(name = "class") val classSummary: ClassSummaryDto,
    val students: List<StudentSummaryDto> = emptyList(),
    val meta: PaginationDto = PaginationDto(),
)

data class AttendanceSummaryDto(
    val present: Int = 0,
    val absent: Int = 0,
    val late: Int = 0,
    val excused: Int = 0,
    val total: Int = 0,
    val rate: Double = 0.0,
)

data class StudentProfileDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String,
    val gender: String? = null,
    val initials: String = "",
    @param:Json(name = "date_of_birth") val dateOfBirth: String? = null,
    @param:Json(name = "admission_date") val admissionDate: String? = null,
    val status: String,
    val attendance: AttendanceSummaryDto,
)

data class StudentProfileResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    @param:Json(name = "class") val classIdentity: ClassIdentityDto,
    val student: StudentProfileDto,
)

data class AttendanceStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String,
    val status: String? = null,
    val remark: String? = null,
)

data class AttendanceSheetResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    @param:Json(name = "class") val classIdentity: ClassIdentityDto,
    val date: String,
    val version: String,
    val students: List<AttendanceStudentDto> = emptyList(),
)

data class AttendanceRecordRequestDto(
    @param:Json(name = "student_id") val studentId: Long,
    val status: String,
    val remark: String? = null,
)

data class SaveAttendanceRequestDto(
    val date: String,
    val version: String,
    @param:Json(name = "request_id") val requestId: String,
    val records: List<AttendanceRecordRequestDto>,
)

data class SaveAttendanceResponseDto(
    val message: String,
    val saved: Int,
    val date: String,
    val version: String,
)

data class StaffAttendanceCountsDto(
    val early: Int = 0,
    val present: Int = 0,
    val late: Int = 0,
    val absent: Int = 0,
)

data class StaffAttendanceTodayDto(
    val status: String,
    @param:Json(name = "clock_in") val clockIn: String? = null,
    @param:Json(name = "clock_out") val clockOut: String? = null,
)

data class StaffAttendanceSettingsDto(
    @param:Json(name = "geo_enabled") val geoEnabled: Boolean = false,
    @param:Json(name = "geo_radius_meters") val geoRadiusMeters: Int = 0,
)

data class StaffAttendanceRecordDto(
    val date: String,
    val status: String,
    @param:Json(name = "clock_in") val clockIn: String? = null,
    @param:Json(name = "clock_out") val clockOut: String? = null,
    val method: String? = null,
)

data class StaffAttendanceResponseDto(
    val month: Int,
    val year: Int,
    val counts: StaffAttendanceCountsDto,
    val today: StaffAttendanceTodayDto? = null,
    val settings: StaffAttendanceSettingsDto,
    val records: List<StaffAttendanceRecordDto> = emptyList(),
)

data class ProxyAttendanceColleagueDto(
    val id: Long,
    val name: String,
    val emp: String? = null,
    val photo: String? = null,
)

data class ProxyAttendanceColleaguesResponseDto(
    val ok: Boolean = true,
    val staff: List<ProxyAttendanceColleagueDto> = emptyList(),
)

data class ProxyClockInRequestDto(
    @param:Json(name = "staff_id") val staffId: Long,
    val token: String,
    val photo: String,
    val lat: Double? = null,
    val lng: Double? = null,
)

data class ClockInRequestDto(
    val token: String,
    val lat: Double? = null,
    val lng: Double? = null,
)

data class EmptyRequestDto(val value: String? = null)
