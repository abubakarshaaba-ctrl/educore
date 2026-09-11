package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class ShellNavigationPolicyTest {
    @Test
    fun teacher_shell_has_five_tabs_and_never_invents_modules() {
        val session = session(portal = "staff", role = "subject_teacher")
        val tabs = ShellNavigationPolicy.tabs(session)

        assertEquals(5, tabs.size)
        assertEquals("Classes", tabs[1].label)
        assertEquals("Timetable", tabs[2].label)

        val exposed = ShellTabId.entries
            .flatMap { ShellNavigationPolicy.modulesFor(it, session) }
            .map(ModuleDescriptor::key)
            .toSet()
        assertTrue(exposed.all(session.modules.map(ModuleDescriptor::key).toSet()::contains))
    }

    @Test
    fun module_hub_groups_every_visible_admin_module_once() {
        val session = session(portal = "admin", role = "admin")
        val visible = ShellNavigationPolicy.visibleModules(session)
        val grouped = ShellNavigationPolicy.groupedModules(session)
            .values
            .flatten()

        assertEquals(visible.size, grouped.size)
        assertEquals(visible.map { it.key }.toSet(), grouped.map { it.key }.toSet())
        assertTrue(grouped.any { it.key == "fees" })
    }

    private fun session(portal: String, role: String): SessionSnapshot = SessionSnapshot(
        user = UserIdentity(
            id = 5,
            name = "A. Teacher",
            email = "teacher@example.test",
            staffId = "STF005",
            roleKey = role,
            roleLabel = "Subject Teacher",
            roles = listOf(role),
            portal = portal,
        ),
        school = SchoolIdentity(2, "Greenfield Academy", "greenfield"),
        academicPeriod = AcademicPeriod(1, "2026/2027", 1, "First Term"),
        access = TenantAccess(true, "allowed", "Available", null, null),
        permissions = setOf("classes", "scores"),
        modules = listOf(
            ModuleDescriptor("classes", "Classes", "/classes", "classes"),
            ModuleDescriptor("scores", "Scores", "/scores", "scores"),
            ModuleDescriptor("timetable", "Timetable", "/timetable", "timetable"),
            ModuleDescriptor("messages", "Messages", "/messages", "messages"),
            ModuleDescriptor("profile", "My Profile", "/profile", "profile"),
            ModuleDescriptor("fees", "Fees", "/fees", "fees"),
        ),
        serverTime = "2026-08-28T08:00:00+01:00",
    )
}
