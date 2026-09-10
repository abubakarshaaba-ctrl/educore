package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class AdminStaffAttendanceResponseDto(
    @Json(name = "date") val date: String,
    @Json(name = "summary") val summary: AdminStaffAttendanceSummaryDto,
    @Json(name = "records") val records: List<AdminStaffAttendanceRecordDto> = emptyList(),
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
data class AdminStaffAttendanceRecordDto(
    @Json(name = "id") val id: Long,
    @Json(name = "staff") val staff: String? = null,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "status") val status: String? = null,
    @Json(name = "clock_in") val clockIn: String? = null,
    @Json(name = "clock_out") val clockOut: String? = null,
    @Json(name = "method") val method: String? = null,
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
