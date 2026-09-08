package online.educoreng.educore.presentation

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.PlatformApi
import online.educoreng.educore.core.network.dto.PlatformAgentsDto
import online.educoreng.educore.core.network.dto.PlatformBillingDto
import online.educoreng.educore.core.network.dto.PlatformDashboardDto
import online.educoreng.educore.core.network.dto.PlatformPlansDto
import online.educoreng.educore.core.network.dto.PlatformTenantsDto
import retrofit2.HttpException

enum class PlatformSection { OVERVIEW, SCHOOLS, BILLING, PLANS, AGENTS }

internal data class PlatformUiState(
    val section: PlatformSection = PlatformSection.OVERVIEW,
    val dashboard: PlatformDashboardDto? = null,
    val tenants: PlatformTenantsDto? = null,
    val billing: PlatformBillingDto? = null,
    val plans: PlatformPlansDto? = null,
    val agents: PlatformAgentsDto? = null,
    val search: String = "",
    val status: String? = null,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
)

@HiltViewModel
internal class PlatformViewModel @Inject constructor(factory: ApiClientFactory) : ViewModel() {
    private val api = factory.create(PlatformApi::class.java)
    private val _uiState = MutableStateFlow(PlatformUiState())
    val uiState: StateFlow<PlatformUiState> = _uiState.asStateFlow()
    private var loadJob: Job? = null

    fun load(section: PlatformSection = _uiState.value.section) {
        loadJob?.cancel()
        loadJob = viewModelScope.launch {
            _uiState.update { it.copy(section = section, isLoading = true, errorMessage = null) }
            try {
                when (section) {
                    PlatformSection.OVERVIEW -> _uiState.update { it.copy(dashboard = api.dashboard()) }
                    PlatformSection.SCHOOLS -> {
                        val state = _uiState.value
                        _uiState.update {
                            it.copy(tenants = api.tenants(state.search.trim().takeIf(String::isNotBlank), state.status))
                        }
                    }
                    PlatformSection.BILLING -> _uiState.update { it.copy(billing = api.billing()) }
                    PlatformSection.PLANS -> _uiState.update { it.copy(plans = api.plans()) }
                    PlatformSection.AGENTS -> _uiState.update { it.copy(agents = api.agents()) }
                }
                _uiState.update { it.copy(isLoading = false) }
            } catch (cancelled: CancellationException) {
                throw cancelled
            } catch (error: Throwable) {
                _uiState.update { it.copy(isLoading = false, errorMessage = error.platformMessage()) }
            }
        }
    }

    fun selectSection(section: PlatformSection) = load(section)

    fun setSearch(value: String) {
        _uiState.update { it.copy(search = value.take(120), errorMessage = null) }
    }

    fun searchSchools() = load(PlatformSection.SCHOOLS)

    fun setStatus(value: String?) {
        _uiState.update { it.copy(status = value, errorMessage = null) }
        load(PlatformSection.SCHOOLS)
    }
}

private fun Throwable.platformMessage(): String = when (this) {
    is HttpException -> when (code()) {
        401 -> "Your platform session has expired. Sign in again."
        403 -> "Platform Super Admin access is required."
        else -> "The platform service returned an error (${code()})."
    }
    else -> localizedMessage?.takeIf(String::isNotBlank)
        ?: "Unable to reach the EduCore platform service. Check your connection and try again."
}
