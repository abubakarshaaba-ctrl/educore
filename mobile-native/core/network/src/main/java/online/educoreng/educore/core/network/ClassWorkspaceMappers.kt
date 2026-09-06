package online.educoreng.educore.core.network

import online.educoreng.educore.core.model.AttendanceSheet
import online.educoreng.educore.core.model.AttendanceStatus
import online.educoreng.educore.core.model.AttendanceStudent
import online.educoreng.educore.core.model.AttendanceSummary
import online.educoreng.educore.core.model.ClassCapabilities
import online.educoreng.educore.core.model.ClassCatalogue
import online.educoreng.educore.core.model.ClassSubject
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.core.model.ClassStudentsSnapshot
import online.educoreng.educore.core.model.StaffAttendanceCounts
import online.educoreng.educore.core.model.StaffAttendanceRecord
import online.educoreng.educore.core.model.StaffAttendanceSnapshot
import online.educoreng.educore.core.model.StaffAttendanceToday
import online.educoreng.educore.core.model.StudentProfile
import online.educoreng.educore.core.model.StudentSummary
import online.educoreng.educore.core.network.dto.AttendanceSheetResponseDto
import online.educoreng.educore.core.network.dto.ClassListResponseDto
import online.educoreng.educore.core.network.dto.ClassStudentsResponseDto
import online.educoreng.educore.core.network.dto.ClassSummaryDto
import online.educoreng.educore.core.network.dto.StaffAttendanceResponseDto
import online.educoreng.educore.core.network.dto.StudentProfileResponseDto
import online.educoreng.educore.core.network.dto.StudentSummaryDto

fun ClassListResponseDto.toDomain(
    cachedAtEpochMs: Long? = null,
    isFromCache: Boolean = false,
): ClassCatalogue = ClassCatalogue(
    classes = classes.map(ClassSummaryDto::toDomain),
    generatedAt = generatedAt,
    cachedAtEpochMs = cachedAtEpochMs,
    isFromCache = isFromCache,
)

fun ClassSummaryDto.toDomain(): ClassSummary = ClassSummary(
    id = id,
    name = name,
    levelName = level?.name,
    trackName = track?.name,
    formTutorName = formTutor?.name,
    roles = roles,
    subjects = subjects.map { subject ->
        ClassSubject(
            id = subject.id,
            name = subject.name,
            code = subject.code,
            teacherId = subject.teacherId,
            teacherName = subject.teacherName,
        )
    },
    studentCount = studentsCount,
    capabilities = ClassCapabilities(
        viewStudents = capabilities.viewStudents,
        markAttendance = capabilities.markAttendance,
        enterScores = capabilities.enterScores,
        viewResults = capabilities.viewResults,
        planLessons = capabilities.planLessons,
    ),
)

fun ClassStudentsResponseDto.toDomain(
    cachedAtEpochMs: Long? = null,
    isFromCache: Boolean = false,
): ClassStudentsSnapshot = ClassStudentsSnapshot(
    classSummary = classSummary.toDomain(),
    students = students.map(StudentSummaryDto::toDomain),
    generatedAt = generatedAt,
    cachedAtEpochMs = cachedAtEpochMs,
    isFromCache = isFromCache,
    currentPage = meta.currentPage,
    lastPage = meta.lastPage,
    total = meta.total,
)

fun StudentSummaryDto.toDomain(): StudentSummary = StudentSummary(
    id = id,
    name = name,
    admissionNumber = admissionNumber,
    gender = gender,
    initials = initials.ifBlank { name.initials() },
)

fun StudentProfileResponseDto.toDomain(): StudentProfile = StudentProfile(
    student = StudentSummary(
        id = student.id,
        name = student.name,
        admissionNumber = student.admissionNumber,
        gender = student.gender,
        initials = student.initials.ifBlank { student.name.initials() },
    ),
    className = classIdentity.name,
    dateOfBirth = student.dateOfBirth,
    admissionDate = student.admissionDate,
    status = student.status,
    attendance = AttendanceSummary(
        present = student.attendance.present,
        absent = student.attendance.absent,
        late = student.attendance.late,
        excused = student.attendance.excused,
        total = student.attendance.total,
        rate = student.attendance.rate,
    ),
)

fun AttendanceSheetResponseDto.toDomain(): AttendanceSheet = AttendanceSheet(
    classId = classIdentity.id,
    className = classIdentity.name,
    date = date,
    version = version,
    students = students.map { item ->
        AttendanceStudent(
            student = StudentSummary(
                id = item.id,
                name = item.name,
                admissionNumber = item.admissionNumber,
                gender = null,
                initials = item.name.initials(),
            ),
            status = AttendanceStatus.fromWire(item.status),
            remark = item.remark,
        )
    },
    generatedAt = generatedAt,
)

fun StaffAttendanceResponseDto.toDomain(): StaffAttendanceSnapshot = StaffAttendanceSnapshot(
    month = month,
    year = year,
    counts = StaffAttendanceCounts(counts.early, counts.present, counts.late, counts.absent),
    today = today?.let { StaffAttendanceToday(it.status, it.clockIn, it.clockOut) },
    geoEnabled = settings.geoEnabled,
    geoRadiusMeters = settings.geoRadiusMeters,
    records = records.map { StaffAttendanceRecord(it.date, it.status, it.clockIn, it.clockOut, it.method) },
)

private fun String.initials(): String = trim()
    .split(Regex("\\s+"))
    .filter(String::isNotBlank)
    .take(2)
    .joinToString("") { part -> part.take(1).uppercase() }
