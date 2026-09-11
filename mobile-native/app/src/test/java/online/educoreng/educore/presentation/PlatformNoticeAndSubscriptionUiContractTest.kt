package online.educoreng.educore.presentation

import java.io.File
import org.junit.Assert.assertTrue
import org.junit.Test

class PlatformNoticeAndSubscriptionUiContractTest {
    @Test
    fun communication_shells_wire_platform_notice_read_and_dismiss_actions() {
        val authorized = source("AuthorizedShell.kt")
        val staff = source("StaffWorkspaceShell.kt")

        listOf(authorized, staff).forEach { shell ->
            assertTrue(shell.contains("onMarkPlatformRead = communicationViewModel::markPlatformRead"))
            assertTrue(shell.contains("onDismissPlatformNotice = communicationViewModel::dismissPlatformNotice"))
            assertTrue(shell.contains("communicationState.totalUnreadNotices"))
        }
    }

    @Test
    fun tenant_admin_home_contains_server_time_subscription_countdown_tile() {
        val source = source("DashboardHomeContent.kt")

        assertTrue(source.contains("if (session.user.portal == \"admin\")"))
        assertTrue(source.contains("SubscriptionCountdownTile(session)"))
        assertTrue(source.contains("session.serverTime?.let(::parseLocalDate)"))
        assertTrue(source.contains("session.access.expiresAt?.let(::parseLocalDate)"))
        assertTrue(source.contains("ChronoUnit.DAYS.between(today, it)"))
        assertTrue(source.contains("remaining != null && remaining <= 7"))
        assertTrue(source.contains("remaining != null && remaining <= 30"))
        assertTrue(source.contains("\"Expires today\""))
        assertTrue(source.contains("\"1 day remaining\""))
        assertTrue(source.contains("\"$remaining days remaining\""))
    }

    private fun source(name: String): String {
        val file = File("src/main/java/online/educoreng/educore/presentation/$name")
        assertTrue("Expected source file ${file.path}", file.isFile)
        return file.readText()
    }
}
