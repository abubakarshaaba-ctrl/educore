package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import javax.inject.Inject
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.ClassWorkspaceRepository
import online.educoreng.educore.core.model.AttendanceSheet
import online.educoreng.educore.core.model.AttendanceStatus
import online.educoreng.educore.core.model.ClassCatalogue
import online.educoreng.educore.core.model.ClassStudentsSnapshot
import online.educoreng.educore.core.model.StaffAttendanceSnapshot
import online.educoreng.educore.core.model.StudentProfile
import online.educoreng.educore.core.model.SyncState
import online.educoreng.educore.sync.OfflineSyncCoordinator

data class ClassesUiState(
    val catalogue: ClassCatalogue? = null,
    val classStudents: ClassStudentsSnapshot? = null,
    val studentProfile: StudentProfile? = null,
    val attendanceSheet: AttendanceSheet? = null,
    val staffAttendance: StaffAttendanceSnapshot? = null,
    val classSearch: String = "",
    val studentSearch: String = "",
    val isLoadingClasses: Boolean = false,
    val isLoadingWorkspace: Boolean = false,
    val isLoadingMoreStudents: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
)

@HiltViewModel
class ClassesViewModel @Inject constructor(
    private val repository: ClassWorkspaceRepository,
    private val syncCoordinator: OfflineSyncCoordinator,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ClassesUiState())
    val uiState: StateFlow<ClassesUiState> = _uiState.asStateFlow()
    private var draftJob: Job? = null
    private var studentSearchJob: Job? = null
    private var studentLoadJob: Job? = null

    init {
        loadClasses()
    }

    fun loadClasses() {
        if (_uiState.value.isLoadingClasses) return
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingClasses = true, errorMessage = null) }
            when (val result = repository.loadClasses(forceRefresh = true)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(catalogue = result.value, isLoadingClasses = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingClasses = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun setClassSearch(value: String) {
        _uiState.update { it.copy(classSearch = value) }
    }

    fun openClass(classId: Long) {
        loadStudents(classId, "")
    }

    fun clearClassSelection() {
        studentSearchJob?.cancel()
        studentLoadJob?.cancel()
        _uiState.update {
            it.copy(
                classStudents = null,
                studentProfile = null,
                attendanceSheet = null,
                studentSearch = "",
                isLoadingWorkspace = false,
                isLoadingMoreStudents = false,
                errorMessage = null,
            )
        }
    }

    fun setStudentSearch(value: String) {
        _uiState.update { it.copy(studentSearch = value) }
        val classId = _uiState.value.classStudents?.classSummary?.id ?: return
        studentSearchJob?.cancel()
        studentSearchJob = viewModelScope.launch {
            delay(350)
            loadStudents(classId, value)
        }
    }

    fun loadStudents(classId: Long, search: String = _uiState.value.studentSearch) {
        studentLoadJob?.cancel()
        studentLoadJob = viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoadingWorkspace = true,
                    isLoadingMoreStudents = false,
                    errorMessage = null,
                    studentProfile = null,
                    attendanceSheet = null,
                    studentSearch = search,
                )
            }
            when (val result = repository.loadStudents(classId, search, page = 1)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(classStudents = result.value, isLoadingWorkspace = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingWorkspace = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun loadMoreStudents() {
        val current = _uiState.value.classStudents ?: return
        if (_uiState.value.isLoadingWorkspace || _uiState.value.isLoadingMoreStudents || current.currentPage >= current.lastPage) return
        studentLoadJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoadingMoreStudents = true, errorMessage = null) }
            when (val result = repository.loadStudents(
                current.classSummary.id,
                _uiState.value.studentSearch,
                page = current.currentPage + 1,
            )) {
                is AppResult.Success -> _uiState.update { state ->
                    state.copy(
                        classStudents = result.value.copy(
                            students = (current.students + result.value.students).distinctBy { it.id },
                            isFromCache = current.isFromCache || result.value.isFromCache,
                        ),
                        isLoadingMoreStudents = false,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingMoreStudents = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun openStudent(classId: Long, studentId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingWorkspace = true, errorMessage = null, studentProfile = null) }
            when (val result = repository.loadStudentProfile(classId, studentId)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(studentProfile = result.value, isLoadingWorkspace = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingWorkspace = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun openAttendance(classId: Long, date: String = today()) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingWorkspace = true, errorMessage = null, attendanceSheet = null) }
            when (val result = repository.loadAttendance(classId, date)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(attendanceSheet = result.value, isLoadingWorkspace = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingWorkspace = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun updateAttendanceStatus(studentId: Long, status: AttendanceStatus) {
        val sheet = _uiState.value.attendanceSheet ?: return
        val updated = sheet.copy(
            students = sheet.students.map { row ->
                if (row.student.id == studentId) row.copy(status = status) else row
            },
            hasLocalDraft = true,
        )
        _uiState.update { it.copy(attendanceSheet = updated) }
        persistDraft(updated)
    }

    fun markAllPresent() {
        val sheet = _uiState.value.attendanceSheet ?: return
        val updated = sheet.copy(
            students = sheet.students.map { row -> row.copy(status = AttendanceStatus.PRESENT) },
            hasLocalDraft = true,
        )
        _uiState.update { it.copy(attendanceSheet = updated) }
        persistDraft(updated)
    }

    fun discardAttendanceDraft() {
        val sheet = _uiState.value.attendanceSheet ?: return
        viewModelScope.launch {
            repository.discardAttendanceDraft(sheet.classId, sheet.date)
            openAttendance(sheet.classId, sheet.date)
        }
    }

    fun submitAttendance() {
        val sheet = _uiState.value.attendanceSheet ?: return
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            draftJob?.join()
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = repository.submitAttendance(sheet)) {
                is AppResult.Success -> _uiState.update {
                    if (result.value.syncState == SyncState.QUEUED) syncCoordinator.schedule()
                    it.copy(
                        attendanceSheet = result.value,
                        isSaving = false,
                        message = if (result.value.syncState == SyncState.QUEUED) "Attendance queued and will sync automatically." else "Attendance saved successfully.",
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun loadStaffAttendance() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingWorkspace = true, errorMessage = null) }
            when (val result = repository.loadStaffAttendance()) {
                is AppResult.Success -> _uiState.update {
                    it.copy(staffAttendance = result.value, isLoadingWorkspace = false)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isLoadingWorkspace = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun clockIn(token: String, latitude: Double?, longitude: Double?) {
        if (_uiState.value.isSaving || token.isBlank()) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = repository.clockIn(token.trim(), latitude, longitude)) {
                is AppResult.Success -> {
                    _uiState.update { it.copy(isSaving = false, message = result.value) }
                    loadStaffAttendance()
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun clockOut() {
        if (_uiState.value.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            when (val result = repository.clockOut()) {
                is AppResult.Success -> {
                    _uiState.update { it.copy(isSaving = false, message = result.value) }
                    loadStaffAttendance()
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isSaving = false, errorMessage = result.error.userMessage)
                }
            }
        }
    }

    fun consumeMessage() {
        _uiState.update { it.copy(message = null) }
    }

    private fun persistDraft(sheet: AttendanceSheet) {
        draftJob?.cancel()
        draftJob = viewModelScope.launch {
            when (val result = repository.saveAttendanceDraft(sheet)) {
                is AppResult.Success -> _uiState.update { current ->
                    val currentSheet = current.attendanceSheet
                    if (currentSheet?.classId == sheet.classId &&
                        currentSheet.date == sheet.date
                    ) {
                        current.copy(attendanceSheet = result.value)
                    } else {
                        current
                    }
                }
                is AppResult.Failure -> _uiState.update { it.copy(errorMessage = result.error.userMessage) }
            }
        }
    }

    private fun today(): String = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date())
}
