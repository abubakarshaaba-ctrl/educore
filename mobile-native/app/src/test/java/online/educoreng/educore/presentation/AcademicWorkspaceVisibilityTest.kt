package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import org.junit.Assert.assertTrue
import org.junit.Test

class AcademicWorkspaceVisibilityTest {
    @Test
    fun admin_keeps_report_cards_even_when_bootstrap_payload_is_stale() {
        val session = snapshot(
            portal = "admin",
            role = "admin",
            permissions = setOf("students", "scores"),
            modules = listOf(
                ModuleDescriptor("students", "Students", "/students", "students"),
                ModuleDescriptor("scores", "Scores", "/scores", "scores"),
            ),
        )

        assertTrue(ShellNavigationPolicy.visibleModules(session).any { it.key == "reports" })
    }

    @Test
    fun subject_teacher_with_scores_entry_grant_gets_score_entry_workspace() {
        val session = snapshot(
            portal = "staff",
            role = "subject_teacher",
            permissions = setOf("scores.entry"),
            modules = emptyList(),
        )

        val scores = ShellNavigationPolicy.visibleModules(session).firstOrNull { it.key == "scores" }
        assertTrue(scores != null)
        assertTrue(scores?.title == "Score Entry")
    }

    @Test
    fun parent_keeps_separate_results_and_attendance_workspaces_for_linked_children() {
        val session = snapshot(
            portal = "parent",
            role = "parent",
            permissions = emptySet(),
            modules = listOf(
                ModuleDescriptor("parent.results", "Results", "/parent/results", "reports"),
                ModuleDescriptor("parent.attendance", "Attendance", "/parent/attendance", "attendance"),
            ),
        )

        val keys = ShellNavigationPolicy.visibleModules(session).map { it.key }.toSet()
        assertTrue("parent.results" in keys)
        assertTrue("parent.attendance" in keys)
    }

    private fun snapshot(
        portal: String,
        role: String,
        permissions: Set<String>,
        modules: List<ModuleDescriptor>,
    ) = SessionSnapshot(
        user = UserIdentity(
            id = 7,
            name = "Test User",
            email = "user@example.test",
            staffId = null,
            roleKey = role,
            roleLabel = role,
            roles = listOf(role),
            portal = portal,
        ),
        school = SchoolIdentity(2, "Greenfield Academy", "greenfield"),
        academicPeriod = AcademicPeriod(1, "2026/2027", 1, "First Term"),
        access = TenantAccess(true, "allowed", "Available", null, null),
        permissions = permissions,
        modules = modules,
        serverTime = "2026-09-13T20:00:00+01:00",
    )
}
