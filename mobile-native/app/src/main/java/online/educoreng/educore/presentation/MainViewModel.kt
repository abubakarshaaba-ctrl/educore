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
        FirebaseMessaging.getInstance().isAutoInitEnabled = true
        viewModelScope.launch {
            connectivityMonitor.isOnline.collect { online ->
                _uiState.update { it.copy(isOnline = online) }
                if (online) {
                    syncCoordinator.schedule()
                    if (_uiState.value.phase == AppPhase.READY) registerPushToken()
                }
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

    fun openWebModule(path: String) {
        if (_uiState.value.isBusy) return

        val normalizedPath = path.trim()
        if (normalizedPath !in EXPLICIT_WEB_ONLY_PATHS) {
            _uiState.update {
                it.copy(
                    isBusy = false,
                    portalUrl = null,
                    message = "This workspace is being moved into the native EduCore app and will not open in your browser.",
                )
            }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isBusy = true, message = null) }
            when (val result = sessionRepository.createPortalSession(normalizedPath)) {
                is AppResult.Success -> _uiState.update {
                    it.copy(isBusy = false, portalUrl = result.value)
                }
                is AppResult.Failure -> _uiState.update {
                    it.copy(isBusy = false, message = result.error.userMessage)
                }
            }
        }
    }

    fun consumePortalUrl() {
        _uiState.update { it.copy(portalUrl = null) }
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
        val online = _uiState.value.isOnline
        _uiState.update { it.copy(isBusy = true, message = null) }
        viewModelScope.launch {
            sessionRepository.logout()
            _uiState.value = AppUiState(
                phase = AppPhase.SIGNED_OUT,
                isOnline = online,
            )
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
        if (!_uiState.value.isOnline || _uiState.value.phase != AppPhase.READY) return
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
                is AppResult.Success -> {
                    val profilePhoto = when (val photo = dashboardRepository.loadProfilePhoto()) {
                        is AppResult.Success -> photo.value
                        is AppResult.Failure -> null
                    }
                    _uiState.update {
                        it.copy(
                            dashboard = DashboardUiState(
                                snapshot = result.value,
                                profilePhoto = profilePhoto,
                                isLoading = false,
                            ),
                        )
                    }
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

    private companion object {
        val EXPLICIT_WEB_ONLY_PATHS: Set<String> = emptySet()
    }
}
