package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class PortalShellPolicyTest {
    @Test
    fun platform_accounts_use_native_platform_shell() {
        assertTrue(PortalShellPolicy.usesPlatformShell(session("platform")))
        assertTrue(PortalShellPolicy.usesPlatformShell(session(" Platform ")))
    }

    @Test
    fun school_accounts_remain_on_native_school_shell() {
        listOf("admin", "staff", "parent", "student").forEach { portal ->
            assertFalse(portal, PortalShellPolicy.usesPlatformShell(session(portal)))
        }
    }

    private fun session(portal: String) = SessionSnapshot(
        user = UserIdentity(
            id = 1,
            name = "Test User",
            email = "test@example.test",
            staffId = null,
            roleKey = portal,
            roleLabel = portal,
            roles = listOf(portal),
            portal = portal,
        ),
        school = SchoolIdentity(null, "EduCore", "platform"),
        academicPeriod = AcademicPeriod(null, null, null, null),
        access = TenantAccess(true, "allowed", "Available", null, null),
        permissions = setOf("*"),
        modules = emptyList(),
        serverTime = null,
    )
}
