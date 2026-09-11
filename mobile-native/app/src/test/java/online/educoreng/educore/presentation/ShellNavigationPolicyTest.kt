package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ShellNavigationPolicyTest {
    @Test
    fun cbt_is_absent_from_mobile_for_every_role() {
        val modules = baseModules() + listOf(
            ModuleDescriptor("cbt", "CBT", "/cbt", "cbt"),
            ModuleDescriptor("cbt-exams", "CBT Exams", "/cbt/exams", "cbt"),
            ModuleDescriptor("examinations", "Examinations", "/examinations", "cbt"),
            ModuleDescriptor("student.exams", "Student Exams", "/student/exams", "cbt"),
            ModuleDescriptor("student.cbt", "Student CBT", "/student/cbt", "cbt"),
            ModuleDescriptor("staff.cbt", "Staff CBT", "/staff/cbt", "cbt"),
        )
        val removed = setOf("cbt", "cbt-exams", "examinations", "student.exams", "student.cbt", "staff.cbt")
        listOf(
            session("admin", "administrator", setOf("*"), modules),
            session("staff", "subject_teacher", setOf("classes.view"), modules),
            session("parent", "parent", emptySet(), modules),
        ).forEach { snapshot ->
            val keys = ShellNavigationPolicy.visibleModules(snapshot).map { it.key.lowercase() }.toSet()
            assertFalse(keys.any { it in removed })
        }
    }

    @Test
    fun results_and_report_cards_are_visible_only_to_parent_portal() {
        val modules = baseModules() + listOf(
            ModuleDescriptor("results", "Results", "/results", "results"),
            ModuleDescriptor("report-cards", "Report Cards", "/report-cards", "results"),
            ModuleDescriptor("parent.results", "Parent Results", "/parent/results", "results"),
            ModuleDescriptor("student.results", "Student Results", "/student/results", "results"),
        )

        val adminKeys = ShellNavigationPolicy.visibleModules(session("admin", "administrator", setOf("*"), modules)).map { it.key }.toSet()
        val staffKeys = ShellNavigationPolicy.visibleModules(session("staff", "subject_teacher", setOf("classes.view"), modules)).map { it.key }.toSet()
        val studentKeys = ShellNavigationPolicy.visibleModules(session("student", "student", emptySet(), modules)).map { it.key }.toSet()
        val parent = session("parent", "parent", emptySet(), modules)
        val parentKeys = ShellNavigationPolicy.visibleModules(parent).map { it.key }.toSet()
        val parentPrimaryKeys = ShellNavigationPolicy.modulesFor(ShellTabId.PRIMARY, parent).map { it.key }.toSet()
        val resultAliases = setOf("results", "report-cards", "parent.results", "student.results")

        assertFalse(adminKeys.any { it in resultAliases })
        assertFalse(staffKeys.any { it in resultAliases })
        assertFalse(studentKeys.any { it in resultAliases })
        assertTrue(parentKeys.any { it in resultAliases })
        assertTrue(parentPrimaryKeys.any { it in resultAliases })
    }

    @Test
    fun accountant_without_academic_permission_has_no_classes_tab() {
        val snapshot = session("staff", "accountant", setOf("finance.manage"), baseModules())
        assertFalse(ShellNavigationPolicy.tabs(snapshot).any { it.label == "Classes" })
        assertFalse(ShellNavigationPolicy.visibleModules(snapshot).any { it.key == "classes" })
    }

    @Test
    fun accountant_with_explicit_classes_permission_may_receive_classes() {
        val snapshot = session("staff", "accountant", setOf("finance.manage", "classes.view"), baseModules())
        assertTrue(ShellNavigationPolicy.visibleModules(snapshot).any { it.key == "classes" })
    }

    @Test
    fun ordinary_staff_keeps_my_attendance_but_not_staff_attendance() {
        val snapshot = session("staff", "subject_teacher", setOf("attendance.self"), baseModules())
        val keys = ShellNavigationPolicy.visibleModules(snapshot).map { it.key }.toSet()
        assertTrue("staff-attendance.self" in keys)
        assertFalse("staff-attendance" in keys)
    }

    @Test
    fun authorized_admin_sees_staff_attendance_and_my_attendance() {
        val snapshot = session("admin", "administrator", setOf("*"), baseModules())
        val keys = ShellNavigationPolicy.visibleModules(snapshot).map { it.key }.toSet()
        assertTrue("staff-attendance" in keys)
        assertTrue("staff-attendance.self" in keys)
    }

    private fun baseModules() = listOf(
        ModuleDescriptor("classes", "Classes", "/classes", "classes"),
        ModuleDescriptor("scores", "Scores", "/scores", "scores"),
        ModuleDescriptor("timetable", "Timetable", "/timetable", "timetable"),
        ModuleDescriptor("messages", "Messages", "/messages", "messages"),
        ModuleDescriptor("profile", "My Profile", "/profile", "profile"),
        ModuleDescriptor("fees", "Fees", "/fees", "fees"),
        ModuleDescriptor("staff-attendance", "Staff Attendance", "/staff-attendance", "attendance"),
        ModuleDescriptor("staff-attendance.self", "My Attendance", "/my-attendance", "attendance"),
    )

    private fun session(portal: String, role: String, permissions: Set<String>, modules: List<ModuleDescriptor>): SessionSnapshot = SessionSnapshot(
        user = UserIdentity(5, "Test User", "user@example.test", "STF005", role, role, listOf(role), portal),
        school = SchoolIdentity(2, "Greenfield Academy", "greenfield"),
        academicPeriod = AcademicPeriod(1, "2026/2027", 1, "First Term"),
        access = TenantAccess(true, "allowed", "Available", null, null),
        permissions = permissions,
        modules = modules,
        serverTime = "2026-09-10T20:00:00+01:00",
    )
}
