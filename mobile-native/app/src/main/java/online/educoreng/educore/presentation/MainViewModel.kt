package online.educoreng.educore.presentation

import android.os.Build
import com.google.firebase.messaging.FirebaseMessaging
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.data.connectivity.ConnectivityMonitor
import online.educoreng.educore.core.data.repository.SessionRepository
import online.educoreng.educore.core.data.repository.DashboardRepository
import online.educoreng.educore.core.data.repository.CommunicationRepository
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.sync.OfflineSyncCoordinator

@HiltViewModel
class MainViewModel @Inject constructor(
    private val sessionRepository: SessionRepository,
    private val dashboardRepository: DashboardRepository,
    private val communicationRepository: CommunicationRepository,
    connectivityMonitor: ConnectivityMonitor,
    private val syncCoordinator: OfflineSyncCoordinator,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AppUiState())
    val uiState: StateFlow<AppUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            connectivityMonitor.isOnline.collect { online ->
                _uiState.update { it.copy(isOnline = online) }
                if (online) syncCoordinator.schedule()
            }
        }
        restoreSession()
    }

    fun login(loginId: String, password: String) {
        if (_uiState.value.isBusy) return
        viewModelScope.launch {
            _uiState.update { it.copy(isBusy = true, message = null, fieldErrors = emptyMap()) }
            when (val result = sessionRepository.login(loginId, password, deviceName())) {
                is AppResult.Success -> showSession(result.value)
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        phase = AppPhase.SIGNED_OUT,
                        isBusy = false,
                        message = result.error.userMessage,
                        fieldErrors = result.error.fieldErrors(),
                    )
                }
            }
        }
    }

    fun showForgotPassword() {
        if (_uiState.value.isBusy) return
        _uiState.update {
            it.copy(
                authMode = AuthMode.FORGOT_PASSWORD,
                message = null,
                fieldErrors = emptyMap(),
            )
        }
    }

    fun showLogin() {
        if (_uiState.value.isBusy) return
        _uiState.update {
            it.copy(
                authMode = AuthMode.LOGIN,
                message = null,
                fieldErrors = emptyMap(),
            )
        }
    }

    fun requestPasswordReset(email: String) {
        if (_uiState.value.isBusy) return
        viewModelScope.launch {
            _uiState.update { it.copy(isBusy = true, message = null, fieldErrors = emptyMap()) }
            when (val result = sessionRepository.requestPasswordReset(email)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        authMode = AuthMode.LOGIN,
                        isBusy = false,
                        message = result.value,
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        isBusy = false,
                        message = result.error.userMessage,
                        fieldErrors = result.error.fieldErrors(),
                    )
                }
            }
        }
    }

    fun consumeMessage() {
        _uiState.update { it.copy(message = null) }
    }

    fun retryBootstrap() {
        if (_uiState.value.isBusy || !sessionRepository.hasStoredToken()) return
        refreshSession()
    }

    fun retryDashboard() {
        if (_uiState.value.dashboard.isLoading || _uiState.value.phase != AppPhase.READY) return
        loadDashboard()
    }

    fun logout() {
        if (_uiState.value.isBusy) return
        _uiState.update { it.copy(isBusy = true, message = null) }
        FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            viewModelScope.launch {
                task.takeIf { it.isSuccessful }?.result?.takeIf(String::isNotBlank)?.let { token ->
                    communicationRepository.unregisterPushToken(token)
                }
                sessionRepository.logout()
                _uiState.value = AppUiState(
                    phase = AppPhase.SIGNED_OUT,
                    isOnline = _uiState.value.isOnline,
                )
            }
        }
    }

    private fun restoreSession() {
        viewModelScope.launch {
            if (!sessionRepository.hasStoredToken()) {
                _uiState.update { it.copy(phase = AppPhase.SIGNED_OUT) }
                return@launch
            }

            val cached = sessionRepository.session.first()
            if (cached != null) showSession(cached)
            refreshSession(cached)
        }
    }

    private fun refreshSession(cached: SessionSnapshot? = _uiState.value.session) {
        viewModelScope.launch {
            _uiState.update { it.copy(isBusy = true, message = null) }
            when (val result = sessionRepository.refresh()) {
                is AppResult.Success -> showSession(result.value)
                is AppResult.Failure -> if (result.error.requiresFreshSignIn()) {
                    sessionRepository.logout()
                    _uiState.update {
                        it.copy(
                            phase = AppPhase.SIGNED_OUT,
                            session = null,
                            isBusy = false,
                            message = result.error.userMessage,
                        )
                    }
                } else if (cached != null) {
                    _uiState.update {
                        it.copy(
                            isBusy = false,
                            message = result.error.userMessage,
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            phase = AppPhase.SIGNED_OUT,
                            isBusy = false,
                            message = result.error.userMessage,
                        )
                    }
                }
            }
        }
    }

    private fun showSession(session: SessionSnapshot) {
        _uiState.update {
            it.copy(
                phase = if (session.access.allowed) AppPhase.READY else AppPhase.BLOCKED,
                session = session,
                isBusy = false,
                fieldErrors = emptyMap(),
                message = if (session.access.allowed && session.access.severity == "warning") {
                    session.access.message
                } else {
                    null
                },
            )
        }
        if (session.access.allowed) {
            registerPushToken()
            loadDashboard()
        }
    }

    private fun registerPushToken() {
        FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            task.takeIf { it.isSuccessful }?.result?.takeIf(String::isNotBlank)?.let { token ->
                viewModelScope.launch { communicationRepository.registerPushToken(token) }
            }
        }
    }

    private fun loadDashboard() {
        if (_uiState.value.dashboard.isLoading) return
        _uiState.update {
            it.copy(dashboard = it.dashboard.copy(isLoading = true, errorMessage = null))
        }
        viewModelScope.launch {
            when (val result = dashboardRepository.load()) {
                is AppResult.Success -> _uiState.update {
                    it.copy(
                        dashboard = DashboardUiState(
                            snapshot = result.value,
                            isLoading = false,
                        ),
                    )
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(
                        dashboard = it.dashboard.copy(
                            isLoading = false,
                            errorMessage = result.error.userMessage,
                        ),
                    )
                }
            }
        }
    }

    private fun deviceName(): String = "${Build.MANUFACTURER} ${Build.MODEL}".trim()

    private fun AppError.requiresFreshSignIn(): Boolean = when (this) {
        is AppError.Unauthenticated,
        is AppError.Forbidden,
        is AppError.SubscriptionRestricted -> true
        else -> false
    }

    private fun AppError.fieldErrors(): Map<String, String> =
        (this as? AppError.Validation)
            ?.fieldErrors
            .orEmpty()
            .mapValues { (_, messages) -> messages.firstOrNull().orEmpty() }
            .filterValues(String::isNotBlank)
}
