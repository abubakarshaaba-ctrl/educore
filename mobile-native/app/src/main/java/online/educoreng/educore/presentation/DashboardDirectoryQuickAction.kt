package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.lifecycle.viewmodel.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import online.educoreng.educore.core.designsystem.component.EduCoreQuickAction
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Home-native launcher for operational workspaces that must not fall through
 * to generic class navigation.
 */
@Composable
internal fun DashboardDirectoryQuickAction(
    session: SessionSnapshot,
    module: ModuleDescriptor?,
    label: String,
    icon: ImageVector,
    onFallback: (ModuleDescriptor) -> Unit,
    modifier: Modifier = Modifier,
) {
    if (module == null) {
        EduCoreQuickAction(
            label = label,
            icon = icon,
            enabled = false,
            onClick = {},
            modifier = modifier,
        )
        return
    }

    when (module.key.lowercase()) {
        "staff" -> StaffDirectoryHomeAction(
            session = session,
            label = label,
            icon = icon,
            modifier = modifier,
        )

        "students" -> if (session.user.portal.equals("admin", ignoreCase = true)) {
            StudentDirectoryHomeAction(
                label = label,
                icon = icon,
                modifier = modifier,
            )
        } else {
            EduCoreQuickAction(
                label = label,
                icon = icon,
                onClick = { onFallback(module) },
                modifier = modifier,
            )
        }

        "staff-attendance" -> if (session.user.portal.equals("admin", ignoreCase = true)) {
            AdminStaffAttendanceHomeAction(
                label = label,
                icon = icon,
                modifier = modifier,
            )
        } else {
            EduCoreQuickAction(
                label = label,
                icon = icon,
                onClick = { onFallback(module) },
                modifier = modifier,
            )
        }

        else -> EduCoreQuickAction(
            label = label,
            icon = icon,
            onClick = { onFallback(module) },
            modifier = modifier,
        )
    }
}

@Composable
private fun StaffDirectoryHomeAction(
    session: SessionSnapshot,
    label: String,
    icon: ImageVector,
    modifier: Modifier,
) {
    val viewModel: StaffDirectoryViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    var open by remember { mutableStateOf(false) }

    EduCoreQuickAction(
        label = label,
        icon = icon,
        onClick = {
            open = true
            viewModel.load()
        },
        modifier = modifier,
    )

    if (open) {
        FullScreenOperationalDialog(onDismiss = { open = false }) {
            StaffDirectoryScreen(
                state = state,
                currentUserId = session.user.id,
                onBack = { open = false },
                onQuery = viewModel::setQuery,
                onFilter = viewModel::setFilter,
                onRefresh = viewModel::load,
                onLoadMore = viewModel::loadMore,
                onToggleActive = viewModel::setActive,
            )
        }
    }
}

@Composable
private fun StudentDirectoryHomeAction(
    label: String,
    icon: ImageVector,
    modifier: Modifier,
) {
    val viewModel: AdminStudentDirectoryViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    var open by remember { mutableStateOf(false) }

    EduCoreQuickAction(
        label = label,
        icon = icon,
        onClick = {
            open = true
            viewModel.load()
        },
        modifier = modifier,
    )

    if (open) {
        FullScreenOperationalDialog(onDismiss = { open = false }) {
            AdminStudentDirectoryScreen(
                state = state,
                onBack = { open = false },
                onRefresh = viewModel::load,
            )
        }
    }
}

@Composable
private fun AdminStaffAttendanceHomeAction(
    label: String,
    icon: ImageVector,
    modifier: Modifier,
) {
    val viewModel: AdminStaffAttendanceViewModel = hiltViewModel()
    val state by viewModel.uiState.collectAsStateWithLifecycle()
    var open by remember { mutableStateOf(false) }

    EduCoreQuickAction(
        label = label,
        icon = icon,
        onClick = {
            open = true
            viewModel.loadDaily()
        },
        modifier = modifier,
    )

    if (open) {
        FullScreenOperationalDialog(onDismiss = { open = false }) {
            AdminStaffAttendanceScreen(
                state = state,
                onBack = { open = false },
                onRefresh = viewModel::loadDaily,
            )
        }
    }
}

@Composable
private fun FullScreenOperationalDialog(
    onDismiss: () -> Unit,
    content: @Composable () -> Unit,
) {
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false),
    ) {
        Surface(
            modifier = Modifier.fillMaxSize(),
            color = EduCoreColors.Page50,
        ) {
            content()
        }
    }
}
