package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.AttendanceSheet
import online.educoreng.educore.core.model.ClassCatalogue
import online.educoreng.educore.core.model.ClassStudentsSnapshot
import online.educoreng.educore.core.model.StaffAttendanceSnapshot
import online.educoreng.educore.core.model.StudentProfile

interface ClassWorkspaceRepository {
    suspend fun loadClasses(forceRefresh: Boolean = false): AppResult<ClassCatalogue>
    suspend fun loadStudents(classId: Long, search: String? = null, page: Int = 1): AppResult<ClassStudentsSnapshot>
    suspend fun loadStudentProfile(classId: Long, studentId: Long): AppResult<StudentProfile>
    suspend fun loadAttendance(classId: Long, date: String): AppResult<AttendanceSheet>
    suspend fun saveAttendanceDraft(sheet: AttendanceSheet): AppResult<AttendanceSheet>
    suspend fun discardAttendanceDraft(classId: Long, date: String): AppResult<Unit>
    suspend fun submitAttendance(sheet: AttendanceSheet): AppResult<AttendanceSheet>
    suspend fun syncPendingAttendance(): Boolean
    suspend fun loadStaffAttendance(): AppResult<StaffAttendanceSnapshot>
    suspend fun clockIn(token: String, latitude: Double?, longitude: Double?): AppResult<String>
    suspend fun clockOut(): AppResult<String>
}
