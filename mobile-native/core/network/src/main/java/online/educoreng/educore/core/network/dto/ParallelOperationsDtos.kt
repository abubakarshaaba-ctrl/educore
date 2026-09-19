package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.ParallelOperationsArm
import online.educoreng.educore.core.model.ParallelOperationsAttendance
import online.educoreng.educore.core.model.ParallelOperationsAttendanceStudent
import online.educoreng.educore.core.model.ParallelOperationsCapabilities
import online.educoreng.educore.core.model.ParallelOperationsClass
import online.educoreng.educore.core.model.ParallelOperationsOption
import online.educoreng.educore.core.model.ParallelOperationsPeriod
import online.educoreng.educore.core.model.ParallelOperationsSelection
import online.educoreng.educore.core.model.ParallelOperationsSession
import online.educoreng.educore.core.model.ParallelOperationsSubject
import online.educoreng.educore.core.model.ParallelOperationsTerm
import online.educoreng.educore.core.model.ParallelOperationsWorkspace
import online.educoreng.educore.core.model.ParallelWorkingDay
import online.educoreng.educore.core.model.ParallelStaffAttendance
import online.educoreng.educore.core.model.ParallelStaffAttendancePerson

data class ParallelOperationsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 2,
    val selected: ParallelOperationsSelectionDto,
    val capabilities: ParallelOperationsCapabilitiesDto,
    val curricula: List<ParallelOperationsOptionDto> = emptyList(),
    val sessions: List<ParallelOperationsSessionDto> = emptyList(),
    val terms: List<ParallelOperationsTermDto> = emptyList(),
    val classes: List<ParallelOperationsClassDto> = emptyList(),
    @param:Json(name = "working_days") val workingDays: List<ParallelWorkingDayDto> = emptyList(),
    val periods: List<ParallelOperationsPeriodDto> = emptyList(),
    val attendance: ParallelOperationsAttendanceDto? = null,
    @param:Json(name = "staff_attendance") val staffAttendance: ParallelStaffAttendanceDto? = null,
)

data class ParallelOperationsSelectionDto(
    @param:Json(name = "parallel_curriculum_id") val curriculumId: Long? = null,
    @param:Json(name = "session_id") val sessionId: Long? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
    @param:Json(name = "class_id") val classId: Long? = null,
    @param:Json(name = "arm_id") val armId: Long? = null,
    val date: String = "",
)

data class ParallelOperationsCapabilitiesDto(
    @param:Json(name = "manage_timetable") val manageTimetable: Boolean = false,
    @param:Json(name = "save_attendance") val saveAttendance: Boolean = false,
    @param:Json(name = "export_attendance") val exportAttendance: Boolean = false,
    @param:Json(name = "manage_working_days") val manageWorkingDays: Boolean = false,
    @param:Json(name = "clock_parallel_staff") val clockParallelStaff: Boolean = false,
)

data class ParallelOperationsOptionDto(
    val id: Long,
    val name: String,
    val code: String? = null,
)

data class ParallelOperationsSessionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class ParallelOperationsTermDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_id") val sessionId: Long,
    @param:Json(name = "session_name") val sessionName: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class ParallelOperationsClassDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    val arms: List<ParallelOperationsArmDto> = emptyList(),
    val subjects: List<ParallelOperationsSubjectDto> = emptyList(),
)

data class ParallelOperationsArmDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    val capacity: Int? = null,
)

data class ParallelOperationsSubjectDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    @param:Json(name = "teacher_id") val teacherId: Long? = null,
    @param:Json(name = "teacher_name") val teacherName: String? = null,
    @param:Json(name = "class_id") val classId: Long,
)

data class ParallelOperationsPeriodDto(
    val id: Long,
    @param:Json(name = "class_id") val classId: Long,
    @param:Json(name = "arm_id") val armId: Long,
    @param:Json(name = "subject_id") val subjectId: Long,
    val subject: String,
    @param:Json(name = "teacher_id") val teacherId: Long? = null,
    val teacher: String? = null,
    @param:Json(name = "day_of_week") val dayOfWeek: String,
    @param:Json(name = "start_time") val startTime: String,
    @param:Json(name = "end_time") val endTime: String,
    val venue: String? = null,
)

data class ParallelOperationsAttendanceDto(
    val date: String,
    val version: String,
    val students: List<ParallelOperationsAttendanceStudentDto> = emptyList(),
)

data class ParallelOperationsAttendanceStudentDto(
    @param:Json(name = "enrolment_id") val enrolmentId: Long,
    @param:Json(name = "student_id") val studentId: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val status: String? = null,
    val remark: String? = null,
)

data class ParallelWorkingDayDto(
    @param:Json(name = "day_of_week") val dayOfWeek: String,
    @param:Json(name = "is_working") val isWorking: Boolean,
    @param:Json(name = "resumption_time") val resumptionTime: String? = null,
    @param:Json(name = "closing_time") val closingTime: String? = null,
    @param:Json(name = "grace_minutes") val graceMinutes: Int = 0,
)

data class ParallelStaffAttendanceDto(
    val date: String,
    @param:Json(name = "day_of_week") val dayOfWeek: String,
    @param:Json(name = "is_working_day") val isWorkingDay: Boolean,
    @param:Json(name = "resumption_time") val resumptionTime: String? = null,
    @param:Json(name = "closing_time") val closingTime: String? = null,
    @param:Json(name = "grace_minutes") val graceMinutes: Int = 0,
    @param:Json(name = "can_clock_self") val canClockSelf: Boolean = false,
    val staff: List<ParallelStaffAttendancePersonDto> = emptyList(),
)

data class ParallelStaffAttendancePersonDto(
    @param:Json(name = "user_id") val userId: Long,
    val name: String,
    val status: String? = null,
    @param:Json(name = "departure_status") val departureStatus: String? = null,
    @param:Json(name = "clock_in_time") val clockInTime: String? = null,
    @param:Json(name = "clock_out_time") val clockOutTime: String? = null,
)

data class ParallelWorkingDaysMutationRequestDto(
    @param:Json(name = "parallel_curriculum_id") val curriculumId: Long,
    val days: List<ParallelWorkingDayRequestDto>,
)

data class ParallelWorkingDayRequestDto(
    @param:Json(name = "day_of_week") val dayOfWeek: String,
    @param:Json(name = "is_working") val isWorking: Boolean,
    @param:Json(name = "resumption_time") val resumptionTime: String? = null,
    @param:Json(name = "closing_time") val closingTime: String? = null,
    @param:Json(name = "grace_minutes") val graceMinutes: Int = 0,
)

data class ParallelWorkingDaysMutationResponseDto(
    val message: String,
    @param:Json(name = "working_days") val workingDays: List<ParallelWorkingDayDto> = emptyList(),
)

data class ParallelStaffAttendanceRequestDto(
    @param:Json(name = "parallel_curriculum_id") val curriculumId: Long,
)

data class ParallelStaffClockResponseDto(
    val message: String,
    val status: String? = null,
    @param:Json(name = "departure_status") val departureStatus: String? = null,
    @param:Json(name = "clock_in_time") val clockInTime: String? = null,
    @param:Json(name = "clock_out_time") val clockOutTime: String? = null,
)

data class ParallelPeriodMutationRequestDto(
    @param:Json(name = "parallel_curriculum_class_id") val classId: Long,
    @param:Json(name = "parallel_curriculum_class_arm_id") val armId: Long,
    @param:Json(name = "parallel_curriculum_subject_id") val subjectId: Long,
    @param:Json(name = "session_id") val sessionId: Long,
    @param:Json(name = "day_of_week") val dayOfWeek: String,
    @param:Json(name = "start_time") val startTime: String,
    @param:Json(name = "end_time") val endTime: String,
    val venue: String? = null,
)

data class ParallelPeriodMutationResponseDto(
    val message: String,
    val period: ParallelOperationsPeriodDto,
)

data class ParallelAttendanceMutationRequestDto(
    @param:Json(name = "parallel_curriculum_class_arm_id") val armId: Long,
    @param:Json(name = "term_id") val termId: Long,
    @param:Json(name = "attendance_date") val attendanceDate: String,
    val version: String? = null,
    val records: List<ParallelAttendanceRecordRequestDto>,
)

data class ParallelAttendanceRecordRequestDto(
    @param:Json(name = "enrolment_id") val enrolmentId: Long,
    val status: String,
    val remark: String? = null,
)

data class ParallelAttendanceMutationResponseDto(
    val message: String,
    val saved: Int = 0,
    val version: String? = null,
    val summary: Map<String, Int> = emptyMap(),
)

fun ParallelOperationsResponseDto.toDomain(): ParallelOperationsWorkspace =
    ParallelOperationsWorkspace(
        selected = ParallelOperationsSelection(
            curriculumId = selected.curriculumId,
            sessionId = selected.sessionId,
            termId = selected.termId,
            classId = selected.classId,
            armId = selected.armId,
            date = selected.date,
        ),
        capabilities = ParallelOperationsCapabilities(
            manageTimetable = capabilities.manageTimetable,
            saveAttendance = capabilities.saveAttendance,
            exportAttendance = capabilities.exportAttendance,
            manageWorkingDays = capabilities.manageWorkingDays,
            clockParallelStaff = capabilities.clockParallelStaff,
        ),
        curricula = curricula.map { ParallelOperationsOption(it.id, it.name, it.code) },
        sessions = sessions.map { ParallelOperationsSession(it.id, it.name, it.isCurrent) },
        terms = terms.map {
            ParallelOperationsTerm(it.id, it.name, it.sessionId, it.sessionName, it.isCurrent)
        },
        classes = classes.map { item ->
            ParallelOperationsClass(
                id = item.id,
                name = item.name,
                code = item.code,
                arms = item.arms.map { ParallelOperationsArm(it.id, it.name, it.code, it.capacity) },
                subjects = item.subjects.map {
                    ParallelOperationsSubject(
                        id = it.id,
                        name = it.name,
                        code = it.code,
                        teacherId = it.teacherId,
                        teacherName = it.teacherName,
                        classId = it.classId,
                    )
                },
            )
        },
        periods = periods.map { it.toDomain() },
        workingDays = workingDays.map {
            ParallelWorkingDay(
                dayOfWeek = it.dayOfWeek,
                isWorking = it.isWorking,
                resumptionTime = it.resumptionTime,
                closingTime = it.closingTime,
                graceMinutes = it.graceMinutes,
            )
        },
        attendance = attendance?.let { sheet ->
            ParallelOperationsAttendance(
                date = sheet.date,
                version = sheet.version,
                students = sheet.students.map {
                    ParallelOperationsAttendanceStudent(
                        enrolmentId = it.enrolmentId,
                        studentId = it.studentId,
                        name = it.name,
                        admissionNumber = it.admissionNumber,
                        status = it.status,
                        remark = it.remark,
                    )
                },
            )
        },
        staffAttendance = staffAttendance?.let { sheet ->
            ParallelStaffAttendance(
                date = sheet.date,
                dayOfWeek = sheet.dayOfWeek,
                isWorkingDay = sheet.isWorkingDay,
                resumptionTime = sheet.resumptionTime,
                closingTime = sheet.closingTime,
                graceMinutes = sheet.graceMinutes,
                canClockSelf = sheet.canClockSelf,
                staff = sheet.staff.map {
                    ParallelStaffAttendancePerson(
                        userId = it.userId,
                        name = it.name,
                        status = it.status,
                        departureStatus = it.departureStatus,
                        clockInTime = it.clockInTime,
                        clockOutTime = it.clockOutTime,
                    )
                },
            )
        },
    )

private fun ParallelOperationsPeriodDto.toDomain() =
    ParallelOperationsPeriod(
        id = id,
        classId = classId,
        armId = armId,
        subjectId = subjectId,
        subject = subject,
        teacherId = teacherId,
        teacher = teacher,
        dayOfWeek = dayOfWeek,
        startTime = startTime,
        endTime = endTime,
        venue = venue,
    )
