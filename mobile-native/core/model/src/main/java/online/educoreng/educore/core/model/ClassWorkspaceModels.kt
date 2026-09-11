package online.educoreng.educore.core.model

data class ClassCapabilities(
    val viewStudents: Boolean,
    val markAttendance: Boolean,
    val enterScores: Boolean,
    val viewResults: Boolean,
    val planLessons: Boolean,
)

data class ClassSubject(
    val id: Long,
    val name: String,
    val code: String?,
    val teacherId: Long?,
    val teacherName: String?,
)

data class ClassSummary(
    val id: Long,
    val name: String,
    val levelName: String?,
    val trackName: String?,
    val formTutorName: String?,
    val roles: List<String>,
    val subjects: List<ClassSubject>,
    val studentCount: Int,
    val capabilities: ClassCapabilities,
)

data class ClassCatalogue(
    val classes: List<ClassSummary>,
    val generatedAt: String,
    val cachedAtEpochMs: Long? = null,
    val isFromCache: Boolean = false,
)

data class StudentSummary(
    val id: Long,
    val name: String,
    val admissionNumber: String,
    val gender: String?,
    val initials: String,
)

data class ClassStudentsSnapshot(
    val classSummary: ClassSummary,
    val students: List<StudentSummary>,
    val generatedAt: String,
    val cachedAtEpochMs: Long? = null,
    val isFromCache: Boolean = false,
    val currentPage: Int = 1,
    val lastPage: Int = 1,
    val total: Int = students.size,
)

data class AttendanceSummary(
    val present: Int,
    val absent: Int,
    val late: Int,
    val excused: Int,
    val total: Int,
    val rate: Double,
)

data class StudentProfile(
    val student: StudentSummary,
    val className: String,
    val dateOfBirth: String?,
    val admissionDate: String?,
    val status: String,
    val attendance: AttendanceSummary,
)

enum class AttendanceStatus(val wireValue: String) {
    PRESENT("present"),
    ABSENT("absent"),
    LATE("late"),
    EXCUSED("excused");

    companion object {
        fun fromWire(value: String?): AttendanceStatus? = entries.firstOrNull { it.wireValue == value }
    }
}

data class AttendanceStudent(
    val student: StudentSummary,
    val status: AttendanceStatus?,
    val remark: String?,
)

data class AttendanceSheet(
    val classId: Long,
    val className: String,
    val date: String,
    val version: String,
    val students: List<AttendanceStudent>,
    val generatedAt: String,
    val hasLocalDraft: Boolean = false,
    val isDraftStale: Boolean = false,
    val draftUpdatedAtEpochMs: Long? = null,
    val syncState: SyncState = SyncState.NONE,
    val syncMessage: String? = null,
)

data class StaffAttendanceCounts(
    val early: Int,
    val present: Int,
    val late: Int,
    val absent: Int,
)

data class StaffAttendanceToday(
    val status: String,
    val clockIn: String?,
    val clockOut: String?,
)

data class StaffAttendanceRecord(
    val date: String,
    val status: String,
    val clockIn: String?,
    val clockOut: String?,
    val method: String?,
)

data class ProxyAttendanceColleague(
    val id: Long,
    val name: String,
    val staffId: String,
    val photoUrl: String?,
)

data class StaffAttendanceSnapshot(
    val month: Int,
    val year: Int,
    val counts: StaffAttendanceCounts,
    val today: StaffAttendanceToday?,
    val geoEnabled: Boolean,
    val geoRadiusMeters: Int,
    val records: List<StaffAttendanceRecord>,
)
