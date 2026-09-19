package online.educoreng.educore.core.model

data class ParallelOperationsWorkspace(
    val selected: ParallelOperationsSelection,
    val capabilities: ParallelOperationsCapabilities,
    val curricula: List<ParallelOperationsOption>,
    val sessions: List<ParallelOperationsSession>,
    val terms: List<ParallelOperationsTerm>,
    val classes: List<ParallelOperationsClass>,
    val periods: List<ParallelOperationsPeriod>,
    val attendance: ParallelOperationsAttendance?,
    val workingDays: List<ParallelWorkingDay> = emptyList(),
    val staffAttendance: ParallelStaffAttendance? = null,
)

data class ParallelOperationsSelection(
    val curriculumId: Long?,
    val sessionId: Long?,
    val termId: Long?,
    val classId: Long?,
    val armId: Long?,
    val date: String,
)

data class ParallelOperationsCapabilities(
    val manageTimetable: Boolean,
    val saveAttendance: Boolean,
    val exportAttendance: Boolean = false,
    val manageWorkingDays: Boolean = false,
    val clockParallelStaff: Boolean = false,
)

data class ParallelOperationsOption(
    val id: Long,
    val name: String,
    val code: String? = null,
)

data class ParallelOperationsSession(
    val id: Long,
    val name: String,
    val isCurrent: Boolean,
)

data class ParallelOperationsTerm(
    val id: Long,
    val name: String,
    val sessionId: Long,
    val sessionName: String? = null,
    val isCurrent: Boolean,
)

data class ParallelOperationsClass(
    val id: Long,
    val name: String,
    val code: String? = null,
    val arms: List<ParallelOperationsArm>,
    val subjects: List<ParallelOperationsSubject>,
)

data class ParallelOperationsArm(
    val id: Long,
    val name: String,
    val code: String? = null,
    val capacity: Int? = null,
)

data class ParallelOperationsSubject(
    val id: Long,
    val name: String,
    val code: String? = null,
    val teacherId: Long? = null,
    val teacherName: String? = null,
    val classId: Long,
)

data class ParallelOperationsPeriod(
    val id: Long,
    val classId: Long,
    val armId: Long,
    val subjectId: Long,
    val subject: String,
    val teacherId: Long? = null,
    val teacher: String? = null,
    val dayOfWeek: String,
    val startTime: String,
    val endTime: String,
    val venue: String? = null,
)

data class ParallelWorkingDay(
    val dayOfWeek: String,
    val isWorking: Boolean,
    val resumptionTime: String? = null,
    val closingTime: String? = null,
    val graceMinutes: Int = 0,
)

data class ParallelWorkingDayDraft(
    val dayOfWeek: String,
    val isWorking: Boolean,
    val resumptionTime: String? = null,
    val closingTime: String? = null,
    val graceMinutes: Int = 0,
)

data class ParallelStaffAttendance(
    val date: String,
    val dayOfWeek: String,
    val isWorkingDay: Boolean,
    val resumptionTime: String? = null,
    val closingTime: String? = null,
    val graceMinutes: Int = 0,
    val canClockSelf: Boolean = false,
    val selfRecord: ParallelStaffAttendanceSelfRecord? = null,
    val staff: List<ParallelStaffAttendancePerson> = emptyList(),
)

data class ParallelStaffAttendanceSelfRecord(
    val status: String? = null,
    val departureStatus: String? = null,
    val clockInTime: String? = null,
    val clockOutTime: String? = null,
)

data class ParallelStaffAttendancePerson(
    val userId: Long,
    val name: String,
    val status: String? = null,
    val departureStatus: String? = null,
    val clockInTime: String? = null,
    val clockOutTime: String? = null,
)

data class ParallelOperationsAttendance(
    val date: String,
    val version: String,
    val students: List<ParallelOperationsAttendanceStudent>,
)

data class ParallelOperationsAttendanceStudent(
    val enrolmentId: Long,
    val studentId: Long,
    val name: String,
    val admissionNumber: String? = null,
    val status: String? = null,
    val remark: String? = null,
)

data class ParallelAttendanceDraft(
    val enrolmentId: Long,
    val status: String,
    val remark: String? = null,
)
