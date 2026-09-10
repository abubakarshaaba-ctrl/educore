package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceResponseDto(
    @Json(name = "date") val date: String,
    @Json(name = "summary") val summary: AdminStaffAttendanceSummaryDto,
    @Json(name = "records") val records: List<AdminStaffAttendanceRecordDto> = emptyList(),
    @Json(name = "pending") val pending: AdminStaffAttendancePendingDto = AdminStaffAttendancePendingDto(),
    @Json(name = "settings") val settings: AdminStaffAttendanceSettingsDto? = null,
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceSummaryDto(
    @Json(name = "eligible") val eligible: Int = 0,
    @Json(name = "clocked_in") val clockedIn: Int = 0,
    @Json(name = "early") val early: Int = 0,
    @Json(name = "present") val present: Int = 0,
    @Json(name = "late") val late: Int = 0,
    @Json(name = "absent") val absent: Int = 0,
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendancePendingDto(
    @Json(name = "offline") val offline: Int = 0,
    @Json(name = "proxy") val proxy: Int = 0,
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceRecordDto(
    @Json(name = "id") val id: Long? = null,
    @Json(name = "user_id") val userId: Long,
    @Json(name = "staff") val staff: String? = null,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "status") val status: String? = null,
    @Json(name = "clock_in") val clockIn: String? = null,
    @Json(name = "clock_out") val clockOut: String? = null,
    @Json(name = "method") val method: String? = null,
    @Json(name = "clocked_in_by") val clockedInBy: String? = null,
    @Json(name = "geo_verified") val geoVerified: Boolean = false,
    @Json(name = "offline") val offline: Boolean = false,
    @Json(name = "notes") val notes: String? = null,
    @Json(name = "proxy_review_status") val proxyReviewStatus: String? = null,
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceSettingsDto(
    @Json(name = "resumption_time") val resumptionTime: String? = null,
    @Json(name = "grace_minutes") val graceMinutes: Int = 0,
    @Json(name = "closing_time") val closingTime: String? = null,
    @Json(name = "geo_enabled") val geoEnabled: Boolean = false,
    @Json(name = "geo_lat") val geoLat: Double? = null,
    @Json(name = "geo_lng") val geoLng: Double? = null,
    @Json(name = "geo_radius_meters") val geoRadiusMeters: Int? = null,
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceReportDto(
    @Json(name = "month") val month: Int,
    @Json(name = "year") val year: Int,
    @Json(name = "working_days") val workingDays: List<String> = emptyList(),
    @Json(name = "staff") val staff: List<AdminStaffAttendanceReportRowDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceReportRowDto(
    @Json(name = "id") val id: Long,
    @Json(name = "name") val name: String,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "early") val early: Int = 0,
    @Json(name = "present") val present: Int = 0,
    @Json(name = "late") val late: Int = 0,
    @Json(name = "absent") val absent: Int = 0,
    @Json(name = "days") val days: Int = 0,
    @Json(name = "punctuality") val punctuality: Int = 0,
    @Json(name = "detail") val detail: List<AdminStaffAttendanceDayDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceDayDto(
    @Json(name = "date") val date: String,
    @Json(name = "status") val status: String,
    @Json(name = "clock_in") val clockIn: String? = null,
    @Json(name = "clock_out") val clockOut: String? = null,
)

@JsonClass(generateAdapter = true)
data class AdminStaffOfflineQueueDto(
    @Json(name = "records") val records: List<AdminStaffOfflineRecordDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class AdminStaffOfflineRecordDto(
    @Json(name = "id") val id: Long,
    @Json(name = "user_id") val userId: Long,
    @Json(name = "staff") val staff: String? = null,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "clocked_by") val clockedBy: String? = null,
    @Json(name = "attendance_date") val attendanceDate: String? = null,
    @Json(name = "clock_in") val clockIn: String? = null,
    @Json(name = "lat") val lat: Double? = null,
    @Json(name = "lng") val lng: Double? = null,
    @Json(name = "status") val status: String? = null,
)

@JsonClass(generateAdapter = true)
data class AdminStaffProxyReviewQueueDto(
    @Json(name = "records") val records: List<AdminStaffProxyReviewDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class AdminStaffProxyReviewDto(
    @Json(name = "id") val id: Long,
    @Json(name = "user_id") val userId: Long,
    @Json(name = "staff") val staff: String? = null,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "attendance_date") val attendanceDate: String? = null,
    @Json(name = "clock_in") val clockIn: String? = null,
    @Json(name = "clocked_in_by") val clockedInBy: String? = null,
    @Json(name = "proxy_review_status") val proxyReviewStatus: String? = null,
    @Json(name = "has_proxy_photo") val hasProxyPhoto: Boolean = false,
    @Json(name = "has_profile_photo") val hasProfilePhoto: Boolean = false,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceMutationResponseDto(
    @Json(name = "message") val message: String? = null,
    @Json(name = "record_id") val recordId: Long? = null,
    @Json(name = "settings") val settings: AdminStaffAttendanceSettingsDto? = null,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceReviewRequestDto(
    @Json(name = "action") val action: String,
    @Json(name = "reason") val reason: String? = null,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceProxyDecisionRequestDto(
    @Json(name = "action") val action: String,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceManualRequestDto(
    @Json(name = "user_id") val userId: Long,
    @Json(name = "attendance_date") val attendanceDate: String,
    @Json(name = "status") val status: String,
    @Json(name = "clock_in_time") val clockInTime: String? = null,
    @Json(name = "clock_out_time") val clockOutTime: String? = null,
    @Json(name = "notes") val notes: String? = null,
)

@JsonClass(generateAdapter = true)
data class AdminAttendanceSettingsRequestDto(
    @Json(name = "resumption_time") val resumptionTime: String,
    @Json(name = "grace_minutes") val graceMinutes: Int,
    @Json(name = "closing_time") val closingTime: String,
    @Json(name = "geo_enabled") val geoEnabled: Boolean,
    @Json(name = "geo_lat") val geoLat: Double? = null,
    @Json(name = "geo_lng") val geoLng: Double? = null,
    @Json(name = "geo_radius_meters") val geoRadiusMeters: Int? = null,
)
