package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Staff workspace entry point for profile self-service.
 * The shared ProfileScreen owns profile editing, password changes, passport
 * upload and staff ID-card view/download so every shell uses one consistent
 * implementation.
 */
@Composable
internal fun StaffProfileScreen(
    session: SessionSnapshot,
    onBack: () -> Unit,
) {
    ProfileScreen(
        session = session,
        onBack = onBack,
    )
}
