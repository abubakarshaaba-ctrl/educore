package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import java.util.concurrent.CancellationException
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.LibraryApi
import online.educoreng.educore.core.network.dto.LibraryBorrowerOptionDto
import online.educoreng.educore.core.network.dto.LibraryBookOptionDto
import online.educoreng.educore.core.network.dto.LibraryIssueRequestDto
import retrofit2.HttpException

enum class LibraryBorrowerType { STUDENT, STAFF }

data class LibraryManagementUiState(
    val books: List<LibraryBookOptionDto> = emptyList(),
    val students: List<LibraryBorrowerOptionDto> = emptyList(),
    val staff: List<LibraryBorrowerOptionDto> = emptyList(),
    val issueOpen: Boolean = false,
    val borrowerType: LibraryBorrowerType = LibraryBorrowerType.STUDENT,
    val selectedBookId: Long? = null,
    val selectedBorrowerId: Long? = null,
    val dueDate: String = "",
    val notes: String = "",
    val loadingOptions: Boolean = false,
    val saving: Boolean = false,
    val message: String? = null,
    val errorMessage: String? = null,
    val refreshVersion: Int = 0,
) {
    val borrowerOptions: List<LibraryBorrowerOptionDto>
        get() = if (borrowerType == LibraryBorrowerType.STUDENT) students else staff

    val canIssue: Boolean
        get() = selectedBookId != null && selectedBorrowerId != null && dueDate.isNotBlank() && !saving
}

@HiltViewModel
class LibraryManagementViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: LibraryApi = factory.create(LibraryApi::class.java)
    private val _uiState = MutableStateFlow(LibraryManagementUiState())
    val uiState: StateFlow<LibraryManagementUiState> = _uiState.asStateFlow()

    fun openIssue() {
        _uiState.update {
            it.copy(
                issueOpen = true,
                selectedBookId = null,
                selectedBorrowerId = null,
                dueDate = "",
                notes = "",
                message = null,
                errorMessage = null,
            )
        }
        loadOptions()
    }

    fun closeIssue() = _uiState.update {
        it.copy(issueOpen = false, errorMessage = null)
    }

    fun selectBook(id: Long?) = _uiState.update {
        it.copy(selectedBookId = id, errorMessage = null)
    }

    fun selectBorrowerType(type: LibraryBorrowerType) = _uiState.update {
        it.copy(borrowerType = type, selectedBorrowerId = null, errorMessage = null)
    }

    fun selectBorrower(id: Long?) = _uiState.update {
        it.copy(selectedBorrowerId = id, errorMessage = null)
    }

    fun setDueDate(value: String) = _uiState.update {
        it.copy(dueDate = value.take(10), errorMessage = null)
    }

    fun setNotes(value: String) = _uiState.update {
        it.copy(notes = value.take(1000), errorMessage = null)
    }

    fun loadOptions() {
        if (_uiState.value.loadingOptions) return
        viewModelScope.launch {
            _uiState.update { it.copy(loadingOptions = true, errorMessage = null) }
            try {
                val response = api.options()
                _uiState.update {
                    it.copy(
                        books = response.books,
                        students = response.students,
                        staff = response.staff,
                        loadingOptions = false,
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (error: Throwable) {
                _uiState.update { it.copy(loadingOptions = false, errorMessage = error.libraryMessage()) }
            }
        }
    }

    fun issue() {
        val state = _uiState.value
        val bookId = state.selectedBookId ?: return
        val borrowerId = state.selectedBorrowerId ?: return
        if (state.dueDate.isBlank() || state.saving) return

        viewModelScope.launch {
            _uiState.update { it.copy(saving = true, errorMessage = null, message = null) }
            try {
                val response = api.issue(
                    LibraryIssueRequestDto(
                        bookId = bookId,
                        studentId = borrowerId.takeIf { state.borrowerType == LibraryBorrowerType.STUDENT },
                        staffId = borrowerId.takeIf { state.borrowerType == LibraryBorrowerType.STAFF },
                        dueDate = state.dueDate.trim(),
                        notes = state.notes.trim().ifBlank { null },
                    )
                )
                _uiState.update {
                    it.copy(
                        saving = false,
                        issueOpen = false,
                        selectedBookId = null,
                        selectedBorrowerId = null,
                        dueDate = "",
                        notes = "",
                        message = response.message,
                        refreshVersion = it.refreshVersion + 1,
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (error: Throwable) {
                _uiState.update { it.copy(saving = false, errorMessage = error.libraryMessage()) }
            }
        }
    }

    fun returnLoan(loanId: String) {
        val id = loanId.toLongOrNull() ?: run {
            _uiState.update { it.copy(errorMessage = "This loan identifier is invalid.") }
            return
        }
        if (_uiState.value.saving) return

        viewModelScope.launch {
            _uiState.update { it.copy(saving = true, errorMessage = null, message = null) }
            try {
                val response = api.returnLoan(id)
                _uiState.update {
                    it.copy(
                        saving = false,
                        message = response.message,
                        refreshVersion = it.refreshVersion + 1,
                    )
                }
            } catch (error: CancellationException) {
                throw error
            } catch (error: Throwable) {
                _uiState.update { it.copy(saving = false, errorMessage = error.libraryMessage()) }
            }
        }
    }

    fun consumeRefresh() = _uiState.update { it.copy(refreshVersion = 0) }
    fun consumeMessage() = _uiState.update { it.copy(message = null) }
}

private fun Throwable.libraryMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account does not have library management permission."
        404 -> "The selected book, borrower or loan is no longer available."
        409 -> "The library record changed before this action completed. Refresh and try again."
        422 -> "Check the selected borrower, book and due date and try again."
        else -> "The library service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to complete the library action. Check your connection and try again."
}
