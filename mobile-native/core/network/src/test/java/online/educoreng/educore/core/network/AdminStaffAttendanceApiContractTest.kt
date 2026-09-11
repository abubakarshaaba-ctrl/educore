package online.educoreng.educore.core.network

import org.junit.Assert.assertEquals
import org.junit.Test
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT

class AdminStaffAttendanceApiContractTest {
    @Test
    fun `administrator staff attendance endpoints match canonical Laravel mobile contract`() {
        val methods = AdminStaffAttendanceApi::class.java.declaredMethods.associateBy { it.name }

        assertEquals("admin/staff-attendance/daily", methods.getValue("daily").getAnnotation(GET::class.java).value)
        assertEquals("admin/staff-attendance/monthly", methods.getValue("report").getAnnotation(GET::class.java).value)
        assertEquals("admin/staff-attendance/settings", methods.getValue("settings").getAnnotation(GET::class.java).value)
        assertEquals("admin/staff-attendance/settings", methods.getValue("updateSettings").getAnnotation(PUT::class.java).value)
        assertEquals("admin/staff-attendance/offline", methods.getValue("offlineQueue").getAnnotation(GET::class.java).value)
        assertEquals("admin/staff-attendance/offline/sync", methods.getValue("syncOffline").getAnnotation(POST::class.java).value)
        assertEquals("admin/staff-attendance/proxy-reviews", methods.getValue("proxyReviews").getAnnotation(GET::class.java).value)
        assertEquals("admin/staff-attendance/proxy-clock", methods.getValue("proxyClock").getAnnotation(POST::class.java).value)
        assertEquals("admin/staff-attendance/qr", methods.getValue("qr").getAnnotation(GET::class.java).value)
        assertEquals("admin/staff-attendance/qr/reset", methods.getValue("resetQr").getAnnotation(POST::class.java).value)
    }

    @Test
    fun `legacy mutation routes remain explicit compatibility calls only`() {
        val methods = AdminStaffAttendanceApi::class.java.declaredMethods.associateBy { it.name }

        assertEquals("admin/staff-attendance/manual", methods.getValue("manualOverride").getAnnotation(POST::class.java).value)
        assertEquals("admin/staff-attendance/offline/{record}", methods.getValue("processOffline").getAnnotation(POST::class.java).value)
        assertEquals("admin/staff-attendance/proxy-reviews/{record}", methods.getValue("decideProxy").getAnnotation(POST::class.java).value)
    }
}
