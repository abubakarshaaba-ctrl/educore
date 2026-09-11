package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class StaffCardAttendanceScanRequestDto(
    @Json(name = "staff_qr_token") val staffQrToken: String? = null,
    @Json(name = "school_qr_token") val schoolQrToken: String? = null,
    @Json(name = "staff_id") val staffId: String? = null,
    @Json(name = "latitude") val latitude: Double? = null,
    @Json(name = "longitude") val longitude: Double? = null,
    @Json(name = "accuracy") val accuracy: Double? = null,
    @Json(name = "device") val device: String? = "android",
)

@JsonClass(generateAdapter = true)
data class StaffCardAttendanceScanResponseDto(
    val success: Boolean = false,
    val action: String? = null,
    @Json(name = "scan_method") val scanMethod: String? = null,
    @Json(name = "server_timestamp") val serverTimestamp: String? = null,
    val message: String,
    val record: StaffCardAttendanceRecordDto? = null,
)

@JsonClass(generateAdapter = true)
data class StaffCardAttendanceRecordDto(
    val id: Long? = null,
    @Json(name = "staff_id") val userId: Long? = null,
    @Json(name = "staff_name") val staffName: String? = null,
    @Json(name = "staff_number") val staffNumber: String? = null,
    val date: String? = null,
    val status: String? = null,
    @Json(name = "clock_in") val clockIn: String? = null,
    @Json(name = "clock_out") val clockOut: String? = null,
    val method: String? = null,
    @Json(name = "recorded_by") val recordedBy: String? = null,
)
