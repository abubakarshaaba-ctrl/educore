package online.educoreng.educore.core.network

import com.squareup.moshi.Json
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceRecordDto
import online.educoreng.educore.core.network.dto.AdminStaffOfflineRecordDto
import online.educoreng.educore.core.network.dto.AdminStaffProxyReviewDto
import org.junit.Assert.assertEquals
import org.junit.Test

class AdminStaffAttendancePayloadContractTest {
    @Test
    fun `daily record DTO uses canonical Laravel staff payload keys`() {
        assertJsonName(AdminStaffAttendanceRecordDto::class.java, "userId", "staff_id")
        assertJsonName(AdminStaffAttendanceRecordDto::class.java, "staff", "staff_name")
        assertJsonName(AdminStaffAttendanceRecordDto::class.java, "staffId", "staff_number")
        assertJsonName(AdminStaffAttendanceRecordDto::class.java, "clockedInBy", "recorded_by")
        assertJsonName(AdminStaffAttendanceRecordDto::class.java, "latitude", "latitude")
        assertJsonName(AdminStaffAttendanceRecordDto::class.java, "longitude", "longitude")
    }

    @Test
    fun `review DTOs use the same canonical record payload`() {
        assertJsonName(AdminStaffOfflineRecordDto::class.java, "userId", "staff_id")
        assertJsonName(AdminStaffOfflineRecordDto::class.java, "attendanceDate", "date")
        assertJsonName(AdminStaffOfflineRecordDto::class.java, "rejectionReason", "rejection_reason")
        assertJsonName(AdminStaffProxyReviewDto::class.java, "userId", "staff_id")
        assertJsonName(AdminStaffProxyReviewDto::class.java, "clockedInBy", "recorded_by")
        assertJsonName(AdminStaffProxyReviewDto::class.java, "proxyReason", "proxy_reason")
    }

    private fun assertJsonName(type: Class<*>, fieldName: String, expected: String) {
        val field = type.getDeclaredField(fieldName)
        assertEquals(expected, field.getAnnotation(Json::class.java)?.name)
    }
}
