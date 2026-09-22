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
    fun five_core_experiences_use_stable_task_oriented_labels() {
        assertEquals(
            listOf("Home", "Schools", "Operations", "Inbox", "More"),
            ShellNavigationPolicy.tabs(session("platform", "super_admin")).map { it.label },
        )
        assertEquals(
            listOf("Home", "Academics", "Operations", "Inbox", "More"),
            ShellNavigationPolicy.tabs(session("admin", "admin")).map { it.label },
        )
        assertEquals(
            listOf("Home", "Classes", "Timetable", "Inbox", "More"),
            ShellNavigationPolicy.tabs(session("staff", "subject_teacher")).map { it.label },
        )
        assertEquals(
            listOf("Home", "Academics", "Timetable", "Inbox", "More"),
            ShellNavigationPolicy.tabs(session("student", "student")).map { it.label },
        )
        assertEquals(
            listOf("Home", "Children", "Academics", "Inbox", "More"),
            ShellNavigationPolicy.tabs(session("parent", "parent")).map { it.label },
        )
    }

    @Test
    fun platform_primary_and_secondary_tabs_do_not_duplicate_school_modules() {
        val session = session("platform", "super_admin").copy(
            modules = listOf(
                ModuleDescriptor("platform.schools", "Schools", "/super/tenants", "schools"),
                ModuleDescriptor("platform.groups", "School Groups", "/super/groups", "schools"),
                ModuleDescriptor("platform.analytics", "Analytics", "/super/analytics", "analytics"),
                ModuleDescriptor("platform.support", "Support", "/super/support", "messages"),
            ),
        )

        val primary = ShellNavigationPolicy.modulesFor(ShellTabId.PRIMARY, session).map { it.key }.toSet()
        val secondary = ShellNavigationPolicy.modulesFor(ShellTabId.SECONDARY, session).map { it.key }.toSet()

        assertEquals(setOf("platform.schools", "platform.groups"), primary)
        assertTrue(primary.intersect(secondary).isEmpty())
    }

    @Test
    fun module_hub_groups_every_visible_backend_module_once() {
        val session = session(portal = "admin", role = "admin")
        val grouped = ShellNavigationPolicy.groupedModules(session)
            .values
            .flatten()

        assertEquals(ShellNavigationPolicy.visibleModules(session).size, grouped.size)
        assertEquals(
            ShellNavigationPolicy.visibleModules(session).map { it.key }.toSet(),
            grouped.map { it.key }.toSet(),
        )
    }

    @Test
    fun student_and_parent_result_modules_are_native_academic_modules() {
        val studentResults = ModuleDescriptor("student.results", "Results", "/student/results", "reports")
        val parentResults = ModuleDescriptor("parent.results", "Results", "/parent/results", "reports")

        assertTrue(!ShellNavigationPolicy.isRemovedFromMobile(studentResults.key))
        assertTrue(!ShellNavigationPolicy.isRemovedFromMobile(parentResults.key))
        assertEquals(ModuleGroup.ACADEMICS, ShellNavigationPolicy.groupFor(studentResults))
        assertEquals(ModuleGroup.ACADEMICS, ShellNavigationPolicy.groupFor(parentResults))
    }

    @Test
    fun learner_attendance_modules_remain_native_and_parent_exposes_attendance_in_children_tab() {
        val studentAttendance = ModuleDescriptor(
            "student.attendance",
            "Attendance",
            "/student/attendance",
            "attendance",
        )
        val parentAttendance = ModuleDescriptor(
            "parent.attendance",
            "Attendance",
            "/parent/attendance",
            "attendance",
        )

        assertTrue(!ShellNavigationPolicy.isRemovedFromMobile(studentAttendance.key))
        assertTrue(!ShellNavigationPolicy.isRemovedFromMobile(parentAttendance.key))
        assertEquals(ModuleGroup.ACADEMICS, ShellNavigationPolicy.groupFor(studentAttendance))
        assertEquals(ModuleGroup.ACADEMICS, ShellNavigationPolicy.groupFor(parentAttendance))

        val parentSession = session(portal = "parent", role = "parent")
            .copy(modules = listOf(parentAttendance))
        assertEquals(
            listOf("parent.attendance"),
            ShellNavigationPolicy.modulesFor(ShellTabId.PRIMARY, parentSession).map { it.key },
        )
    }

    @Test
    fun parallel_curriculum_module_is_native_academic_module() {
        val module = ModuleDescriptor(
            "parallel-curriculum",
            "Parallel Curriculum",
            "/parallel-curriculum",
            "curriculum",
        )

        assertTrue(!ShellNavigationPolicy.isRemovedFromMobile(module.key))
        assertEquals(ModuleGroup.ACADEMICS, ShellNavigationPolicy.groupFor(module))
    }

    private fun session(portal: String, role: String): SessionSnapshot = SessionSnapshot(
        user = UserIdentity(
            id = 5,
            name = "A. User",
            email = "user@example.test",
            staffId = "STF005",
            roleKey = role,
            roleLabel = role.replace("_", " "),
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
