package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdminAttendanceSettingsRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceWorkingDayRequestDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceResponseDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceSummaryDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceWorkingDayDto
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class AdminStaffAttendanceWorkHoursContractTest {
    @Test
    fun `settings request carries independent conventional working days`() {
        val days = listOf(
            AdminAttendanceWorkingDayRequestDto(
                dayOfWeek = "monday",
                isWorking = true,
                resumptionTime = "07:30",
                closingTime = "16:00",
                graceMinutes = 10,
            ),
            AdminAttendanceWorkingDayRequestDto(
                dayOfWeek = "saturday",
                isWorking = false,
            ),
        )

        val request = AdminAttendanceSettingsRequestDto(
            resumptionTime = "07:30",
            graceMinutes = 10,
            closingTime = "16:00",
            workingDays = days,
            geoEnabled = false,
        )

        assertEquals(2, request.workingDays.size)
        assertTrue(request.workingDays.first().isWorking)
        assertEquals("07:30", request.workingDays.first().resumptionTime)
        assertFalse(request.workingDays.last().isWorking)
    }

    @Test
    fun `daily response exposes selected day schedule and full work week`() {
        val monday = AdminStaffAttendanceWorkingDayDto(
            dayOfWeek = "monday",
            isWorking = true,
            resumptionTime = "07:30",
            closingTime = "16:00",
            graceMinutes = 10,
        )
        val saturday = AdminStaffAttendanceWorkingDayDto(
            dayOfWeek = "saturday",
            isWorking = false,
        )

        val response = AdminStaffAttendanceResponseDto(
            date = "2026-09-21",
            summary = AdminStaffAttendanceSummaryDto(),
            daySchedule = monday,
            workingDays = listOf(monday, saturday),
        )

        assertEquals("monday", response.daySchedule?.dayOfWeek)
        assertEquals("16:00", response.daySchedule?.closingTime)
        assertEquals(2, response.workingDays.size)
        assertFalse(response.workingDays.last().isWorking)
    }
}
