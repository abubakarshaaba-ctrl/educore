package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.SessionSnapshot

internal object PortalShellPolicy {
    fun usesPlatformShell(session: SessionSnapshot): Boolean =
        session.user.portal.trim().equals("platform", ignoreCase = true)
}
