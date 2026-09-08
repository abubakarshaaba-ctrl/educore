package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.InventoryApi
import online.educoreng.educore.core.network.dto.InventoryAssetDto
import online.educoreng.educore.core.network.dto.InventoryAssetRequestDto
import online.educoreng.educore.core.network.dto.InventoryWorkspaceDto
import retrofit2.HttpException

internal enum class InventoryField {
    NAME,
    CATEGORY,
    SERIAL_NUMBER,
    LOCATION,
    PURCHASE_DATE,
    PURCHASE_COST,
    NOTES,
}

internal data class InventoryDraft(
    val id: Long? = null,
    val name: String = "",
    val category: String = "",
    val serialNumber: String = "",
    val location: String = "",
    val assignedTo: Long? = null,
    val purchaseDate: String = "",
    val purchaseCost: String = "",
    val condition: String = "good",
    val status: String = "in_storage",
    val notes: String = "",
) {
    val purchaseCostValid: Boolean
        get() = purchaseCost.isBlank() || purchaseCost.trim().toDoubleOrNull()?.let { it >= 0.0 } == true

    val valid: Boolean
        get() = name.isNotBlank() && condition.isNotBlank() && status.isNotBlank() && purchaseCostValid

    fun toRequest() = InventoryAssetRequestDto(
        name = name.trim(),
        category = category.trim().ifBlank { null },
        serialNumber = serialNumber.trim().ifBlank { null },
        location = location.trim().ifBlank { null },
        assignedTo = assignedTo,
        purchaseDate = purchaseDate.trim().ifBlank { null },
        purchaseCost = purchaseCost.trim().takeIf { it.isNotBlank() }?.toDouble(),
        condition = condition,
        status = status,
        notes = notes.trim().ifBlank { null },
    )

    companion object {
        fun from(asset: InventoryAssetDto) = InventoryDraft(
            id = asset.id,
            name = asset.name,
            category = asset.category.orEmpty(),
            serialNumber = asset.serialNumber.orEmpty(),
            location = asset.location.orEmpty(),
            assignedTo = asset.assignedTo,
            purchaseDate = asset.purchaseDate.orEmpty(),
            purchaseCost = asset.purchaseCost?.toString().orEmpty(),
            condition = asset.condition,
            status = asset.status,
            notes = asset.notes.orEmpty(),
        )
    }
}

internal data class InventoryUiState(
    val workspace: InventoryWorkspaceDto? = null,
    val assets: List<InventoryAssetDto> = emptyList(),
    val query: String = "",
    val statusFilter: String = "all",
    val editorOpen: Boolean = false,
    val draft: InventoryDraft = InventoryDraft(),
    val isLoading: Boolean = false,
    val isLoadingMore: Boolean = false,
    val isSaving: Boolean = false,
    val errorMessage: String? = null,
    val message: String? = null,
) {
    val canManage: Boolean get() = workspace?.capabilities?.manage == true
    val hasMore: Boolean get() = workspace?.meta?.hasMore == true
    val editing: Boolean get() = draft.id != null
}

@HiltViewModel
internal class InventoryViewModel @Inject constructor(
    factory: ApiClientFactory,
) : ViewModel() {
    private val api: InventoryApi = factory.create(InventoryApi::class.java)
    private val _uiState = MutableStateFlow(InventoryUiState())
    val uiState: StateFlow<InventoryUiState> = _uiState.asStateFlow()

    fun load() = loadPage(reset = true)
    fun setQuery(value: String) = _uiState.update { it.copy(query = value.take(120), errorMessage = null) }
    fun search() = loadPage(reset = true)
    fun loadMore() = loadPage(reset = false)

    fun setStatusFilter(value: String) {
        if (value == _uiState.value.statusFilter) return
        _uiState.update { it.copy(statusFilter = value, errorMessage = null) }
        loadPage(reset = true)
    }

    fun create() = _uiState.update {
        it.copy(editorOpen = true, draft = InventoryDraft(), errorMessage = null, message = null)
    }

    fun edit(asset: InventoryAssetDto) = _uiState.update {
        it.copy(editorOpen = true, draft = InventoryDraft.from(asset), errorMessage = null, message = null)
    }

    fun closeEditor() = _uiState.update {
        it.copy(editorOpen = false, draft = InventoryDraft(), errorMessage = null)
    }

    fun updateField(field: InventoryField, value: String) = _uiState.update { state ->
        val draft = when (field) {
            InventoryField.NAME -> state.draft.copy(name = value.take(150))
            InventoryField.CATEGORY -> state.draft.copy(category = value.take(100))
            InventoryField.SERIAL_NUMBER -> state.draft.copy(serialNumber = value.take(100))
            InventoryField.LOCATION -> state.draft.copy(location = value.take(150))
            InventoryField.PURCHASE_DATE -> state.draft.copy(purchaseDate = value.take(10))
            InventoryField.PURCHASE_COST -> state.draft.copy(purchaseCost = value.take(20))
            InventoryField.NOTES -> state.draft.copy(notes = value.take(2000))
        }
        state.copy(draft = draft, errorMessage = null)
    }

    fun setAssignedTo(value: Long?) = _uiState.update { it.copy(draft = it.draft.copy(assignedTo = value), errorMessage = null) }
    fun setCondition(value: String) = _uiState.update { it.copy(draft = it.draft.copy(condition = value), errorMessage = null) }
    fun setStatus(value: String) = _uiState.update { it.copy(draft = it.draft.copy(status = value), errorMessage = null) }

    fun save() {
        val state = _uiState.value
        if (!state.canManage || state.isSaving) return
        if (!state.draft.valid) {
            _uiState.update {
                it.copy(
                    errorMessage = if (!state.draft.purchaseCostValid) {
                        "Purchase cost must be a valid non-negative number."
                    } else {
                        "Asset name, condition and status are required."
                    }
                )
            }
            return
        }
        val draft = state.draft

        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching {
                if (draft.id == null) api.create(draft.toRequest())
                else api.update(draft.id, draft.toRequest())
            }.onSuccess { response ->
                _uiState.update {
                    it.copy(
                        editorOpen = false,
                        draft = InventoryDraft(),
                        isSaving = false,
                        message = response.message,
                    )
                }
                loadPage(reset = true, preserveMessage = true)
            }.onFailure { error ->
                _uiState.update { it.copy(isSaving = false, errorMessage = error.inventoryMessage()) }
            }
        }
    }

    fun delete(assetId: Long) {
        val state = _uiState.value
        if (!state.canManage || state.isSaving) return
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null, message = null) }
            runCatching { api.delete(assetId) }
                .onSuccess { response ->
                    _uiState.update {
                        it.copy(
                            editorOpen = false,
                            draft = InventoryDraft(),
                            isSaving = false,
                            message = response.message,
                        )
                    }
                    loadPage(reset = true, preserveMessage = true)
                }
                .onFailure { error ->
                    _uiState.update { it.copy(isSaving = false, errorMessage = error.inventoryMessage()) }
                }
        }
    }

    private fun loadPage(reset: Boolean, preserveMessage: Boolean = false) {
        val current = _uiState.value
        if ((reset && current.isLoading) || (!reset && (current.isLoadingMore || !current.hasMore))) return
        val page = if (reset) 1 else (current.workspace?.meta?.page ?: 1) + 1

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = reset,
                    isLoadingMore = !reset,
                    errorMessage = null,
                    message = if (preserveMessage) it.message else null,
                )
            }
            runCatching {
                api.index(
                    search = _uiState.value.query.trim().ifBlank { null },
                    status = _uiState.value.statusFilter,
                    page = page,
                )
            }.onSuccess { workspace ->
                _uiState.update { state ->
                    state.copy(
                        workspace = workspace,
                        assets = if (reset) workspace.assets else (state.assets + workspace.assets).distinctBy { it.id },
                        query = workspace.selected.search,
                        statusFilter = workspace.selected.status,
                        isLoading = false,
                        isLoadingMore = false,
                    )
                }
            }.onFailure { error ->
                _uiState.update {
                    it.copy(isLoading = false, isLoadingMore = false, errorMessage = error.inventoryMessage())
                }
            }
        }
    }
}

private fun Throwable.inventoryMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your session has expired. Sign in again."
        403 -> "Your account is not permitted to manage inventory."
        404 -> "This asset is no longer available."
        422 -> "Check the asset details, assigned staff, status and condition, then try again."
        else -> "The inventory service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the inventory service. Check your connection and try again."
}
