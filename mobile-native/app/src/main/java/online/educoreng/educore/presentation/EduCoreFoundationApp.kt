package online.educoreng.educore.presentation

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import androidx.activity.compose.ReportDrawnWhen
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreTheme

private val NATIVE_PORTALS = setOf("staff", "admin", "parent")

@Composable
fun EduCoreFoundationApp(viewModel: MainViewModel = hiltViewModel()) {
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    val snackbarHostState = remember { SnackbarHostState() }
    val context = LocalContext.current
    val notificationPermission = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { }
    ReportDrawnWhen { state.phase != AppPhase.STARTING }

    LaunchedEffect(state.phase, state.session?.user?.portal) {
        val portal = state.session?.user?.portal?.lowercase()
        if (state.phase == AppPhase.READY && portal in NATIVE_PORTALS && Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) notificationPermission.launch(Manifest.permission.POST_NOTIFICATIONS)
    }

    LaunchedEffect(state.message) {
        state.message?.let { snackbarHostState.showSnackbar(it); viewModel.consumeMessage() }
    }

    LaunchedEffect(state.portalUrl) {
        state.portalUrl?.let { url ->
            runCatching { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url))) }
                .onFailure { snackbarHostState.showSnackbar("No browser is available to open this workspace.") }
            viewModel.consumePortalUrl()
        }
    }

    EduCoreTheme {
        Box(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
            when (state.phase) {
                // Android itself owns the unavoidable launch transition. There is no
                // branded/interstitial Compose splash and no artificial delay.
                AppPhase.STARTING -> Unit
                AppPhase.SIGNED_OUT -> AuthenticationScreen(
                    state = state,
                    onLogin = viewModel::login,
                    onForgotPassword = viewModel::showForgotPassword,
                    onRequestReset = viewModel::requestPasswordReset,
                    onBackToLogin = viewModel::showLogin,
                )
                AppPhase.BLOCKED -> AccessBlockedScreen(
                    session = requireNotNull(state.session), busy = state.isBusy, online = state.isOnline,
                    onRetry = viewModel::retryBootstrap, onLogout = viewModel::logout,
                )
                AppPhase.READY -> {
                    val rawSession = requireNotNull(state.session)
                    val session = rawSession.copy(modules = ShellNavigationPolicy.visibleModules(rawSession))
                    when (session.user.portal.lowercase()) {
                        "staff", "admin" -> StaffWorkspaceShell(
                            session = session, online = state.isOnline, busy = state.isBusy, dashboard = state.dashboard,
                            snackbarHostState = snackbarHostState, onRefresh = viewModel::retryBootstrap,
                            onRefreshDashboard = viewModel::retryDashboard, onLogout = viewModel::logout,
                        )
                        "parent" -> AuthorizedShell(
                            session = session, online = state.isOnline, busy = state.isBusy, dashboard = state.dashboard,
                            snackbarHostState = snackbarHostState, onRefresh = viewModel::retryBootstrap,
                            onRefreshDashboard = viewModel::retryDashboard, onOpenWebModule = viewModel::openWebModule,
                            onLogout = viewModel::logout,
                        )
                        else -> WebOnlyPortalScreen(session.user.portal, session.user.name, viewModel::logout)
                    }
                }
            }
            SnackbarHost(snackbarHostState, Modifier.align(Alignment.BottomCenter).padding(horizontal = 16.dp, vertical = 88.dp))
        }
    }
}
