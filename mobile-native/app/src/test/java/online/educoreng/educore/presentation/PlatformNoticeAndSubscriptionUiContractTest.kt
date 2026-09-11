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

        assertTrue(source.contains("session.school.id != null && session.user.portal == \"admin\""))
        assertTrue(source.contains("SubscriptionCountdownTile(session)"))
        assertTrue(source.contains("session.serverTime?.let(::parseLocalDate)"))
        assertTrue(source.contains("session.access.expiresAt?.let(::parseLocalDate)"))
        assertTrue(source.contains("ChronoUnit.DAYS.between(today, it)"))
        assertTrue(source.contains("remaining != null && remaining in 0..30"))
        assertTrue(source.contains("\"Expires today\""))
        assertTrue(source.contains("\"1 day remaining\""))
        assertTrue(source.contains("\"\$remaining days remaining\""))
    }

    @Test
    fun subscription_countdown_prioritizes_grace_and_free_states_over_past_expiry_date() {
        val source = source("DashboardHomeContent.kt")

        assertTrue(source.contains("val freePlan = state.contains(\"free\")"))
        assertTrue(source.contains("val gracePeriod = state.contains(\"grace\")"))
        assertTrue(source.contains("val suspended = state.contains(\"suspend\")"))
        assertTrue(source.contains("remaining < 0 && !gracePeriod && !freePlan"))
        assertTrue(source.contains("\"Grace period active\""))
        assertTrue(source.contains("\"Subscription suspended\""))
        assertTrue(source.contains("\"Subscription expired\""))
        assertTrue(source.contains("session.access.message.takeIf(String::isNotBlank)"))
    }

    private fun source(name: String): String {
        val file = File("src/main/java/online/educoreng/educore/presentation/$name")
        assertTrue("Expected source file ${file.path}", file.isFile)
        return file.readText()
    }
}
