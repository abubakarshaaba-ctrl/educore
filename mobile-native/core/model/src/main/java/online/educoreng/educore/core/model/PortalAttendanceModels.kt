package online.educoreng.educore.core.model

data class PortalAttendanceWorkspace(
    val student: PortalAttendanceStudent,
    val children: List<PortalAttendanceChild>,
    val terms: List<PortalAttendanceTerm>,
    val selectedTermId: Long?,
    val conventional: PortalAttendanceSection,
    val parallelProgrammes: List<PortalAttendanceProgramme>,
)

data class PortalAttendanceStudent(
    val id: Long,
    val name: String,
    val admissionNumber: String? = null,
)

data class PortalAttendanceChild(
    val id: Long,
    val name: String,
)

data class PortalAttendanceTerm(
    val id: Long,
    val name: String,
    val sessionName: String? = null,
    val isCurrent: Boolean = false,
)

data class PortalAttendanceSummary(
    val total: Int,
    val present: Int,
    val absent: Int,
    val late: Int,
    val excused: Int,
    val rate: Double,
)

data class PortalAttendanceRecord(
    val date: String?,
    val status: String,
    val remark: String? = null,
)

data class PortalAttendanceSection(
    val stats: PortalAttendanceSummary,
    val records: List<PortalAttendanceRecord>,
)

data class PortalAttendanceProgramme(
    val curriculumId: Long,
    val curriculumName: String,
    val className: String? = null,
    val armName: String? = null,
    val stats: PortalAttendanceSummary,
    val records: List<PortalAttendanceRecord>,
)
