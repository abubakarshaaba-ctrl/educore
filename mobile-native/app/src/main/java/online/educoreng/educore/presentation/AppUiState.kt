package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.DashboardSnapshot

enum class AppPhase {
    STARTING,
    SIGNED_OUT,
    READY,
    BLOCKED,
}

enum class AuthMode {
    LOGIN,
    FORGOT_PASSWORD,
}

data class AppUiState(
    val phase: AppPhase = AppPhase.STARTING,
    val session: SessionSnapshot? = null,
    val isOnline: Boolean = true,
    val isBusy: Boolean = false,
    val message: String? = null,
    val authMode: AuthMode = AuthMode.LOGIN,
    val fieldErrors: Map<String, String> = emptyMap(),
    val dashboard: DashboardUiState = DashboardUiState(),
)

data class DashboardUiState(
    val snapshot: DashboardSnapshot? = null,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
)
