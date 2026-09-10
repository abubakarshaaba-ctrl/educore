package online.educoreng.educore.core.network

import com.squareup.moshi.Json
import kotlin.reflect.full.findAnnotation
import kotlin.reflect.full.memberProperties
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceRecordDto
import online.educoreng.educore.core.network.dto.AdminStaffOfflineRecordDto
import online.educoreng.educore.core.network.dto.AdminStaffProxyReviewDto
import org.junit.Assert.assertEquals
import org.junit.Test

class AdminStaffAttendancePayloadContractTest {
    @Test
    fun `daily record DTO uses canonical Laravel staff payload keys`() {
        assertJsonName<AdminStaffAttendanceRecordDto>("userId", "staff_id")
        assertJsonName<AdminStaffAttendanceRecordDto>("staff", "staff_name")
        assertJsonName<AdminStaffAttendanceRecordDto>("staffId", "staff_number")
        assertJsonName<AdminStaffAttendanceRecordDto>("clockedInBy", "recorded_by")
        assertJsonName<AdminStaffAttendanceRecordDto>("latitude", "latitude")
        assertJsonName<AdminStaffAttendanceRecordDto>("longitude", "longitude")
    }

    @Test
    fun `review DTOs use the same canonical record payload`() {
        assertJsonName<AdminStaffOfflineRecordDto>("userId", "staff_id")
        assertJsonName<AdminStaffOfflineRecordDto>("attendanceDate", "date")
        assertJsonName<AdminStaffOfflineRecordDto>("rejectionReason", "rejection_reason")
        assertJsonName<AdminStaffProxyReviewDto>("userId", "staff_id")
        assertJsonName<AdminStaffProxyReviewDto>("clockedInBy", "recorded_by")
        assertJsonName<AdminStaffProxyReviewDto>("proxyReason", "proxy_reason")
    }

    private inline fun <reified T : Any> assertJsonName(propertyName: String, expected: String) {
        val property = T::class.memberProperties.first { it.name == propertyName }
        assertEquals(expected, property.findAnnotation<Json>()?.name)
    }
}
