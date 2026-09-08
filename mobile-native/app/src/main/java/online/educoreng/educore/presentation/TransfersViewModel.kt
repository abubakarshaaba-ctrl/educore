package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.TransfersApi
import online.educoreng.educore.core.network.dto.CrossSchoolTransferRequestDto
import online.educoreng.educore.core.network.dto.TransfersWorkspaceDto
import retrofit2.HttpException

enum class TransferWorkspaceTab(val label: String) {
    CROSS_SCHOOL("Cross-school"),
    INTERCLASS("Interclass"),
}

enum class TransferConfirmationAction { APPROVE, REJECT }

internal data class TransferConfirmation(
    val transferId: Long,
    val studentName: String,
    val action: TransferConfirmationAction,
)

internal data class TransfersUiState(
    val workspace: TransfersWorkspaceDto? = null,
    val tab: TransferWorkspaceTab = TransferWorkspaceTab.CROSS_SCHOOL,
    val selectedStudentId: Long? = null,
    val selectedDestinationId: Long? = null,
    val reason: String = "",
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
    val confirmation: TransferConfirmation? = null,
) {
    val canSubmitRequest: Boolean get() =
        workspace?.capabilities?.crossSchoolRequest == true &&
            selectedStudentId != null && selectedDestinationId != null && !isMutating
}

@HiltViewModel
internal class TransfersViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: TransfersApi = factory.create(TransfersApi::class.java)
    private val _uiState = MutableStateFlow(TransfersUiState())
    val uiState: StateFlow<TransfersUiState> = _uiState.asStateFlow()

    fun load() {
        if (_uiState.value.isLoading) return
        viewModelScope.launch { loadWorkspace(showSpinner = true) }
    }

    fun selectTab(tab: TransferWorkspaceTab) = _uiState.update {
        it.copy(tab = tab, errorMessage = null, confirmation = null)
    }

    fun selectStudent(id: Long?) = _uiState.update {
        it.copy(selectedStudentId = id, errorMessage = null, message = null)
    }

    fun selectDestination(id: Long?) = _uiState.update {
        it.copy(selectedDestinationId = id, errorMessage = null, message = null)
    }

    fun updateReason(value: String) = _uiState.update {
        it.copy(reason = value.take(1000), errorMessage = null, message = null)
    }

    fun requestTransfer() {
        val state = _uiState.value
        val studentId = state.selectedStudentId ?: return
        val destinationId = state.selectedDestinationId ?: return
        if (!state.canSubmitRequest) return

        mutate {
            api.requestCrossSchool(
                CrossSchoolTransferRequestDto(
                    studentId = studentId,
                    toTenantId = destinationId,
                    reason = state.reason.trim().takeIf(String::isNotBlank),
                )
            ).message
        }
    }

    fun requestApproval(transferId: Long, studentName: String) = _uiState.update {
        it.copy(
            confirmation = TransferConfirmation(transferId, studentName, TransferConfirmationAction.APPROVE),
            errorMessage = null,
        )
    }

    fun requestRejection(transferId: Long, studentName: String) = _uiState.update {
        it.copy(
            confirmation = TransferConfirmation(transferId, studentName, TransferConfirmationAction.REJECT),
            errorMessage = null,
        )
    }

    fun cancelConfirmation() = _uiState.update { it.copy(confirmation = null) }

    fun confirmAction() {
        val confirmation = _uiState.value.confirmation ?: return
        _uiState.update { it.copy(confirmation = null) }
        mutate {
            when (confirmation.action) {
                TransferConfirmationAction.APPROVE -> api.approveCrossSchool(confirmation.transferId).message
                TransferConfirmationAction.REJECT -> api.rejectCrossSchool(confirmation.transferId).message
            }
        }
    }

    fun consumeMessage() = _uiState.update { it.copy(message = null) }

    private fun mutate(action: suspend () -> String) {
        if (_uiState.value.isMutating) return
        viewModelScope.launch {
            _uiState.update { it.copy(isMutating = true, errorMessage = null, message = null) }
            try {
                val message = action()
                _uiState.update {
                    it.copy(
                        isMutating = false,
                        selectedStudentId = null,
                        selectedDestinationId = null,
                        reason = "",
                        message = message,
                    )
                }
                loadWorkspace(showSpinner = false, preserveMessage = true)
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isMutating = false, errorMessage = error.transferMessage()) }
            }
        }
    }

    private suspend fun loadWorkspace(showSpinner: Boolean, preserveMessage: Boolean = false) {
        if (showSpinner) {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        }
        try {
            val workspace = api.index()
            _uiState.update { state ->
                state.copy(
                    workspace = workspace,
                    isLoading = false,
                    errorMessage = null,
                    message = if (preserveMessage) state.message else null,
                )
            }
        } catch (cancelled: CancellationException) {
            throw cancelled
        } catch (error: Throwable) {
            _uiState.update { it.copy(isLoading = false, errorMessage = error.transferMessage()) }
        }
    }
}

private fun Throwable.transferMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "You do not have permission to perform that transfer action."
        404 -> "The student, school or transfer record is no longer available."
        409 -> "The student no longer belongs to the originating school. Reload the workspace."
        422 -> "The transfer is no longer valid in its current state. Reload and try again."
        else -> "The Transfers service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the Transfers service. Check your connection and try again."
}
