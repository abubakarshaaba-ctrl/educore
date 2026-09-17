package online.educoreng.educore.presentation

import androidx.compose.runtime.Composable
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Shared profile entry point for shells that still route through ProfileScreen.
 * StaffProfileScreen owns the canonical self-service implementation so profile
 * editing, passport uploads, password changes and staff-card behavior cannot
 * drift between navigation shells.
 */
@Composable
internal fun ProfileScreen(
    session: SessionSnapshot,
    onBack: () -> Unit,
) {
    StaffProfileScreen(session = session, onBack = onBack)
}
