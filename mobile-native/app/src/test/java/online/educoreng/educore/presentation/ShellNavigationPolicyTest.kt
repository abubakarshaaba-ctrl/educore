package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
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

    @Test
    fun legacy_staff_attendance_is_management_only_and_normalized() {
        val descriptor = ModuleDescriptor("staff-attendance", "Staff Attendance", "/staff-attendance", "staff-attendance")
        val admin = session(portal = "admin", role = "admin", extraModules = listOf(descriptor))
        val leadership = session(portal = "staff", role = "vice_principal_academics", extraModules = listOf(descriptor))
        val ordinaryStaff = session(portal = "staff", role = "subject_teacher", extraModules = listOf(descriptor))

        assertTrue(ShellNavigationPolicy.visibleModules(admin).any { it.key == "staff-attendance.admin" })
        assertTrue(ShellNavigationPolicy.visibleModules(leadership).any { it.key == "staff-attendance.admin" })
        assertFalse(ShellNavigationPolicy.visibleModules(admin).any { it.key == "staff-attendance" })
        assertFalse(ShellNavigationPolicy.visibleModules(ordinaryStaff).any { it.key.startsWith("staff-attendance") })
    }

    @Test
    fun staff_report_aliases_are_restored_and_normalized_to_one_report_cards_module() {
        val aliases = listOf(
            ModuleDescriptor("results", "Results", "/results", "results"),
            ModuleDescriptor("report-cards", "Report Cards", "/report-cards", "report-cards"),
            ModuleDescriptor("reports", "Reports", "/reports", "reports"),
        )
        val staff = session(portal = "staff", role = "subject_teacher", extraModules = aliases)
        val visibleReports = ShellNavigationPolicy.visibleModules(staff).filter { it.key == "reports" }

        assertEquals(1, visibleReports.size)
        assertEquals("Report Cards", visibleReports.single().title)
        assertFalse(ShellNavigationPolicy.visibleModules(staff).any { it.key == "results" || it.key == "report-cards" })
    }

    @Test
    fun cbt_and_examinations_remain_removed_from_mobile() {
        val removed = listOf(
            ModuleDescriptor("cbt", "CBT", "/cbt", "cbt"),
            ModuleDescriptor("cbt-exams", "CBT Exams", "/cbt-exams", "cbt-exams"),
            ModuleDescriptor("examinations", "Examinations", "/examinations", "examinations"),
            ModuleDescriptor("student.exams", "Student Exams", "/student/exams", "student.exams"),
        )
        val staff = session(portal = "staff", role = "subject_teacher", extraModules = removed)
        val keys = ShellNavigationPolicy.visibleModules(staff).map { it.key }.toSet()

        assertFalse(keys.any { it in setOf("cbt", "cbt-exams", "examinations", "student.exams") })
    }

    @Test
    fun accountant_does_not_receive_classes_or_unrelated_academic_modules() {
        val academic = listOf(
            ModuleDescriptor("classes", "Classes", "/classes", "classes"),
            ModuleDescriptor("subjects", "Subjects", "/subjects", "subjects"),
            ModuleDescriptor("lesson-planner", "Lesson Planner", "/lesson-planner", "lesson-planner"),
            ModuleDescriptor("results", "Results", "/results", "results"),
            ModuleDescriptor("report-cards", "Report Cards", "/report-cards", "report-cards"),
        )
        val accountant = session(portal = "staff", role = "accountant", extraModules = academic)
        val keys = ShellNavigationPolicy.visibleModules(accountant).map { it.key }.toSet()

        assertFalse("classes" in keys)
        assertFalse("subjects" in keys)
        assertFalse("lesson-planner" in keys)
        assertFalse("results" in keys)
        assertFalse("report-cards" in keys)
        assertFalse("reports" in keys)
        assertTrue("fees" in keys)
    }

    private fun session(
        portal: String,
        role: String,
        extraModules: List<ModuleDescriptor> = emptyList(),
    ): SessionSnapshot {
        val modules = (listOf(
            ModuleDescriptor("classes", "Classes", "/classes", "classes"),
            ModuleDescriptor("scores", "Scores", "/scores", "scores"),
            ModuleDescriptor("timetable", "Timetable", "/timetable", "timetable"),
            ModuleDescriptor("messages", "Messages", "/messages", "messages"),
            ModuleDescriptor("profile", "My Profile", "/profile", "profile"),
            ModuleDescriptor("fees", "Fees", "/fees", "fees"),
        ) + extraModules).distinctBy { it.key }

        return SessionSnapshot(
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
            // Bootstrap descriptors and effective permissions are a paired server contract.
            // Test fixtures must grant the descriptors they deliberately expose so the
            // navigation policy is exercised after RBAC verification rather than failing
            // because the fixture itself represents an impossible server response.
            permissions = modules.map(ModuleDescriptor::key).toSet(),
            modules = modules,
            serverTime = "2026-08-28T08:00:00+01:00",
        )
    }
}
