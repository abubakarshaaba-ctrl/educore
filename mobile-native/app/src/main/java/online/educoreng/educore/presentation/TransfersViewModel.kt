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
import online.educoreng.educore.core.network.dto.InterclassTransferRequestDto
import online.educoreng.educore.core.network.dto.TransferReasonRequestDto
import online.educoreng.educore.core.network.dto.TransfersWorkspaceDto
import retrofit2.HttpException

enum class TransferWorkspaceTab(val label: String) {
    CROSS_SCHOOL("Cross-school"),
    INTRA_CLASS("Intra-class"),
    INTERCLASS("Interclass"),
}

enum class TransferConfirmationTarget { CROSS_SCHOOL, INTRA_CLASS, INTERCLASS }
enum class TransferConfirmationAction { APPROVE, REJECT, CANCEL }

internal data class TransferConfirmation(
    val target: TransferConfirmationTarget,
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
    val classStudentId: Long? = null,
    val classDestinationArmId: Long? = null,
    val classEffectiveDate: String = "",
    val classReason: String = "",
    val actionReason: String = "",
    val isLoading: Boolean = false,
    val isMutating: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
    val confirmation: TransferConfirmation? = null,
) {
    val canSubmitRequest: Boolean get() =
        workspace?.capabilities?.crossSchoolRequest == true &&
            selectedStudentId != null && selectedDestinationId != null && !isMutating

    val classDestinationOptions get() = run {
        val student = workspace?.options?.students?.firstOrNull { it.id == classStudentId }
        val currentLevel = student?.classLevelId
        workspace?.options?.classArms.orEmpty().filter { arm ->
            arm.id != student?.classArmId && when (tab) {
                TransferWorkspaceTab.INTRA_CLASS -> currentLevel != null && arm.classLevelId == currentLevel
                TransferWorkspaceTab.INTERCLASS -> currentLevel != null && arm.classLevelId != null && arm.classLevelId != currentLevel
                TransferWorkspaceTab.CROSS_SCHOOL -> false
            }
        }
    }

    val canSubmitClassTransfer: Boolean get() {
        val capability = when (tab) {
            TransferWorkspaceTab.INTRA_CLASS -> workspace?.capabilities?.intraClassRequest == true
            TransferWorkspaceTab.INTERCLASS -> workspace?.capabilities?.interclassRequest == true
            TransferWorkspaceTab.CROSS_SCHOOL -> false
        }
        return capability &&
            classStudentId != null &&
            classDestinationArmId != null &&
            classDestinationOptions.any { it.id == classDestinationArmId } &&
            CLASS_TRANSFER_DATE.matches(classEffectiveDate.trim()) &&
            classReason.isNotBlank() &&
            !isMutating
    }
}

private val CLASS_TRANSFER_DATE = Regex("\\d{4}-\\d{2}-\\d{2}")

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
        it.copy(
            tab = tab,
            classDestinationArmId = null,
            errorMessage = null,
            confirmation = null,
            actionReason = "",
        )
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

    fun selectClassStudent(id: Long?) = _uiState.update { state ->
        state.copy(
            classStudentId = id,
            classDestinationArmId = null,
            errorMessage = null,
            message = null,
        )
    }

    fun selectClassDestination(id: Long?) = _uiState.update { state ->
        state.copy(
            classDestinationArmId = id?.takeIf { candidate -> state.classDestinationOptions.any { it.id == candidate } },
            errorMessage = null,
            message = null,
        )
    }

    fun updateClassEffectiveDate(value: String) = _uiState.update {
        it.copy(classEffectiveDate = value.take(10), errorMessage = null, message = null)
    }

    fun updateClassReason(value: String) = _uiState.update {
        it.copy(classReason = value.take(2000), errorMessage = null, message = null)
    }

    fun updateActionReason(value: String) = _uiState.update {
        it.copy(actionReason = value.take(2000), errorMessage = null)
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

    fun requestClassTransfer() {
        val state = _uiState.value
        val studentId = state.classStudentId ?: return
        val destinationId = state.classDestinationArmId ?: return
        if (!state.canSubmitClassTransfer) return

        val body = InterclassTransferRequestDto(
            studentId = studentId,
            toClassArmId = destinationId,
            effectiveDate = state.classEffectiveDate.trim(),
            reason = state.classReason.trim(),
        )
        mutate {
            when (state.tab) {
                TransferWorkspaceTab.INTRA_CLASS -> api.requestIntraClass(body).message
                TransferWorkspaceTab.INTERCLASS -> api.requestInterclass(body).message
                TransferWorkspaceTab.CROSS_SCHOOL -> error("Class transfer tab required.")
            }
        }
    }

    fun requestApproval(transferId: Long, studentName: String) = setConfirmation(
        TransferConfirmationTarget.CROSS_SCHOOL,
        TransferConfirmationAction.APPROVE,
        transferId,
        studentName,
    )

    fun requestRejection(transferId: Long, studentName: String) = setConfirmation(
        TransferConfirmationTarget.CROSS_SCHOOL,
        TransferConfirmationAction.REJECT,
        transferId,
        studentName,
    )

    fun requestClassApproval(transferId: Long, studentName: String) = setConfirmation(
        currentClassTarget(),
        TransferConfirmationAction.APPROVE,
        transferId,
        studentName,
    )

    fun requestClassRejection(transferId: Long, studentName: String) = setConfirmation(
        currentClassTarget(),
        TransferConfirmationAction.REJECT,
        transferId,
        studentName,
    )

    fun requestClassCancellation(transferId: Long, studentName: String) = setConfirmation(
        currentClassTarget(),
        TransferConfirmationAction.CANCEL,
        transferId,
        studentName,
    )

    private fun currentClassTarget(): TransferConfirmationTarget = when (_uiState.value.tab) {
        TransferWorkspaceTab.INTRA_CLASS -> TransferConfirmationTarget.INTRA_CLASS
        TransferWorkspaceTab.INTERCLASS -> TransferConfirmationTarget.INTERCLASS
        TransferWorkspaceTab.CROSS_SCHOOL -> error("Class transfer tab required.")
    }

    private fun setConfirmation(
        target: TransferConfirmationTarget,
        action: TransferConfirmationAction,
        transferId: Long,
        studentName: String,
    ) = _uiState.update {
        it.copy(
            confirmation = TransferConfirmation(target, transferId, studentName, action),
            actionReason = "",
            errorMessage = null,
        )
    }

    fun cancelConfirmation() = _uiState.update {
        it.copy(confirmation = null, actionReason = "")
    }

    fun confirmAction() {
        val state = _uiState.value
        val confirmation = state.confirmation ?: return
        val classTarget = confirmation.target != TransferConfirmationTarget.CROSS_SCHOOL
        if (
            classTarget &&
            confirmation.action in setOf(TransferConfirmationAction.REJECT, TransferConfirmationAction.CANCEL) &&
            state.actionReason.isBlank()
        ) {
            _uiState.update { it.copy(errorMessage = "Enter a reason before confirming this class transfer action.") }
            return
        }

        _uiState.update { it.copy(confirmation = null, actionReason = "") }
        mutate {
            when (confirmation.target) {
                TransferConfirmationTarget.CROSS_SCHOOL -> when (confirmation.action) {
                    TransferConfirmationAction.APPROVE -> api.approveCrossSchool(confirmation.transferId).message
                    TransferConfirmationAction.REJECT -> api.rejectCrossSchool(confirmation.transferId).message
                    TransferConfirmationAction.CANCEL -> error("Cross-school cancellation is not supported.")
                }
                TransferConfirmationTarget.INTRA_CLASS -> when (confirmation.action) {
                    TransferConfirmationAction.APPROVE -> api.approveIntraClass(confirmation.transferId).message
                    TransferConfirmationAction.REJECT -> api.rejectIntraClass(
                        confirmation.transferId,
                        TransferReasonRequestDto(state.actionReason.trim()),
                    ).message
                    TransferConfirmationAction.CANCEL -> api.cancelIntraClass(
                        confirmation.transferId,
                        TransferReasonRequestDto(state.actionReason.trim()),
                    ).message
                }
                TransferConfirmationTarget.INTERCLASS -> when (confirmation.action) {
                    TransferConfirmationAction.APPROVE -> api.approveInterclass(confirmation.transferId).message
                    TransferConfirmationAction.REJECT -> api.rejectInterclass(
                        confirmation.transferId,
                        TransferReasonRequestDto(state.actionReason.trim()),
                    ).message
                    TransferConfirmationAction.CANCEL -> api.cancelInterclass(
                        confirmation.transferId,
                        TransferReasonRequestDto(state.actionReason.trim()),
                    ).message
                }
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
                        classStudentId = null,
                        classDestinationArmId = null,
                        classEffectiveDate = "",
                        classReason = "",
                        actionReason = "",
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
        404 -> "The student, school, class or transfer record is no longer available."
        409 -> "The transfer state changed while you were working. Reload the workspace."
        422 -> "The transfer is no longer valid in its current state. Check the student enrollment, class level and destination arm, then try again."
        else -> "The Transfers service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the Transfers service. Check your connection and try again."
}
