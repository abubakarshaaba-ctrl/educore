package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.ParallelOperationsArmDto
import online.educoreng.educore.core.network.dto.ParallelOperationsAttendanceDto
import online.educoreng.educore.core.network.dto.ParallelOperationsAttendanceStudentDto
import online.educoreng.educore.core.network.dto.ParallelOperationsCapabilitiesDto
import online.educoreng.educore.core.network.dto.ParallelOperationsClassDto
import online.educoreng.educore.core.network.dto.ParallelOperationsOptionDto
import online.educoreng.educore.core.network.dto.ParallelOperationsPeriodDto
import online.educoreng.educore.core.network.dto.ParallelOperationsResponseDto
import online.educoreng.educore.core.network.dto.ParallelOperationsSelectionDto
import online.educoreng.educore.core.network.dto.ParallelOperationsSessionDto
import online.educoreng.educore.core.network.dto.ParallelOperationsSubjectDto
import online.educoreng.educore.core.network.dto.ParallelOperationsTermDto
import online.educoreng.educore.core.network.dto.ParallelWorkingDayDto
import online.educoreng.educore.core.network.dto.ParallelStaffAttendanceDto
import online.educoreng.educore.core.network.dto.ParallelStaffAttendancePersonDto
import online.educoreng.educore.core.network.dto.ParallelStaffAttendanceSelfRecordDto
import online.educoreng.educore.core.network.dto.toDomain
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class ParallelOperationsDtoMapperTest {
    @Test
    fun `maps timetable capabilities and attendance without losing placement context`() {
        val workspace = ParallelOperationsResponseDto(
            selected = ParallelOperationsSelectionDto(
                curriculumId = 10,
                sessionId = 20,
                termId = 21,
                classId = 30,
                armId = 40,
                date = "2026-09-19",
            ),
            capabilities = ParallelOperationsCapabilitiesDto(
                manageTimetable = true,
                saveAttendance = true,
                exportAttendance = true,
                manageWorkingDays = true,
                clockParallelStaff = true,
            ),
            curricula = listOf(ParallelOperationsOptionDto(10, "Islamiyyah", "ISL")),
            sessions = listOf(ParallelOperationsSessionDto(20, "2026/2027", true)),
            terms = listOf(
                ParallelOperationsTermDto(
                    id = 21,
                    name = "First Term",
                    sessionId = 20,
                    sessionName = "2026/2027",
                    isCurrent = true,
                ),
            ),
            classes = listOf(
                ParallelOperationsClassDto(
                    id = 30,
                    name = "Mutawassitah 1",
                    code = "M1",
                    arms = listOf(ParallelOperationsArmDto(40, "A", "A", 30)),
                    subjects = listOf(
                        ParallelOperationsSubjectDto(
                            id = 50,
                            name = "Qur'an",
                            code = "QRN",
                            teacherId = 60,
                            teacherName = "Teacher One",
                            classId = 30,
                        ),
                    ),
                ),
            ),
            workingDays = listOf(
                ParallelWorkingDayDto("monday", true, "15:30", "18:00", 10),
                ParallelWorkingDayDto("saturday", true, "08:00", "13:00", 20),
            ),
            periods = listOf(
                ParallelOperationsPeriodDto(
                    id = 70,
                    classId = 30,
                    armId = 40,
                    subjectId = 50,
                    subject = "Qur'an",
                    teacherId = 60,
                    teacher = "Teacher One",
                    dayOfWeek = "monday",
                    startTime = "09:00",
                    endTime = "09:40",
                    venue = "Room 2",
                ),
            ),
            staffAttendance = ParallelStaffAttendanceDto(
                date = "2026-09-19",
                dayOfWeek = "saturday",
                isWorkingDay = true,
                resumptionTime = "08:00",
                closingTime = "13:00",
                graceMinutes = 20,
                canClockSelf = true,
                selfRecord = ParallelStaffAttendanceSelfRecordDto(
                    status = "present",
                    clockInTime = "07:58",
                ),
                staff = listOf(
                    ParallelStaffAttendancePersonDto(
                        userId = 60,
                        name = "Teacher One",
                        status = "present",
                        departureStatus = null,
                        clockInTime = "07:58",
                        clockOutTime = null,
                    ),
                ),
            ),
            attendance = ParallelOperationsAttendanceDto(
                date = "2026-09-19",
                version = "version-1",
                isWorkingDay = true,
                students = listOf(
                    ParallelOperationsAttendanceStudentDto(
                        enrolmentId = 80,
                        studentId = 90,
                        name = "Amina Bello",
                        admissionNumber = "STU001",
                        status = "late",
                        remark = "Traffic",
                    ),
                ),
            ),
        ).toDomain()

        assertEquals(40L, workspace.selected.armId)
        assertTrue(workspace.capabilities.manageTimetable)
        assertTrue(workspace.capabilities.saveAttendance)
        assertTrue(workspace.capabilities.exportAttendance)
        assertTrue(workspace.capabilities.manageWorkingDays)
        assertTrue(workspace.capabilities.clockParallelStaff)
        assertEquals("15:30", workspace.workingDays.first().resumptionTime)
        assertEquals("13:00", workspace.workingDays.last().closingTime)
        assertTrue(workspace.staffAttendance?.canClockSelf == true)
        assertEquals("07:58", workspace.staffAttendance?.selfRecord?.clockInTime)
        assertEquals("Teacher One", workspace.staffAttendance?.staff?.single()?.name)
        assertEquals("Qur'an", workspace.classes.single().subjects.single().name)
        assertEquals("Teacher One", workspace.periods.single().teacher)
        assertEquals("09:00", workspace.periods.single().startTime)
        assertEquals("Amina Bello", workspace.attendance?.students?.single()?.name)
        assertEquals("late", workspace.attendance?.students?.single()?.status)
        assertEquals("version-1", workspace.attendance?.version)
        assertTrue(workspace.attendance?.isWorkingDay == true)
    }
}
