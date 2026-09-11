package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

enum class ShellTabId(val route: String) {
    HOME("home"), PRIMARY("primary"), SECONDARY("secondary"), INBOX("inbox"), MORE("more"),
}

data class ShellTab(val id: ShellTabId, val label: String)

enum class ModuleGroup(val label: String) {
    ACADEMICS("Academics"), SCHEDULE("Schedule"), OPERATIONS("Operations"), COMMUNICATION("Communication"), ACCOUNT("Account"),
}

object ShellNavigationPolicy {
    fun tabs(session: SessionSnapshot): List<ShellTab> {
        val portal = session.user.portal.lowercase()
        val roleKeys = roleKeys(session)
        val accountant = roleKeys.any { it in ACCOUNTANT_ROLE_KEYS }
        val management = portal == "admin" || portal == "platform"
        val visible = visibleModules(session)

        val tabs = mutableListOf(ShellTab(ShellTabId.HOME, "Home"))
        if (!(accountant && !hasExplicitAcademicPermission(session))) {
            val primaryLabel = when {
                portal == "parent" -> "Children"
                portal == "platform" -> "Schools"
                roleKeys.any { it.contains("teacher") } || portal in setOf("staff", "admin") -> "Classes"
                else -> "Academics"
            }
            if (modulesFor(ShellTabId.PRIMARY, session).isNotEmpty()) tabs += ShellTab(ShellTabId.PRIMARY, primaryLabel)
        }

        val secondaryLabel = when {
            management -> "Operations"
            portal == "parent" -> "Academics"
            else -> "Timetable"
        }
        if (modulesFor(ShellTabId.SECONDARY, session).isNotEmpty()) tabs += ShellTab(ShellTabId.SECONDARY, secondaryLabel)

        tabs += ShellTab(
            ShellTabId.INBOX,
            if (visible.any { it.key.contains("message", true) || it.key.contains("support", true) }) "Inbox" else "Notices",
        )
        tabs += ShellTab(ShellTabId.MORE, "More")
        return tabs
    }

    fun modulesFor(tab: ShellTabId, session: SessionSnapshot): List<ModuleDescriptor> {
        val modules = visibleModules(session)
        val grouped = modules.groupBy(::groupFor)
        return when (tab) {
            ShellTabId.HOME -> emptyList()
            ShellTabId.PRIMARY -> when (session.user.portal.lowercase()) {
                "platform" -> modules.filter { it.key.contains("school", true) || it.key.contains("tenant", true) || it.key.contains("group", true) }
                "parent" -> modules.filter { it.key.contains("attendance", true) }
                "staff", "admin" -> modules.filter { it.key.lowercase() in CLASS_WORKSPACE_ENTRY_KEYS }
                else -> grouped[ModuleGroup.ACADEMICS].orEmpty()
            }
            ShellTabId.SECONDARY -> when (session.user.portal.lowercase()) {
                "admin", "platform" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                "parent" -> modules.filter {
                    groupFor(it) in setOf(ModuleGroup.ACADEMICS, ModuleGroup.OPERATIONS) && it !in modulesFor(ShellTabId.PRIMARY, session)
                }
                else -> grouped[ModuleGroup.SCHEDULE].orEmpty()
            }
            ShellTabId.INBOX -> grouped[ModuleGroup.COMMUNICATION].orEmpty()
            ShellTabId.MORE -> {
                val alreadyPlaced = (modulesFor(ShellTabId.PRIMARY, session) + modulesFor(ShellTabId.SECONDARY, session) + modulesFor(ShellTabId.INBOX, session))
                    .map { canonicalKey(it.key) }.toSet()
                modules.filterNot { canonicalKey(it.key) in alreadyPlaced }
            }
        }
    }

    /** Server bootstrap remains authoritative; this layer only removes mobile-only exclusions. */
    fun visibleModules(session: SessionSnapshot): List<ModuleDescriptor> {
        val portal = session.user.portal.lowercase()
        val accountant = roleKeys(session).any { it in ACCOUNTANT_ROLE_KEYS }
        val accountantAcademicOverride = hasExplicitAcademicPermission(session)
        val canManageStaffAttendance = canManageStaffAttendance(session)

        var filtered = session.modules.filterNot { module ->
            val key = module.key.lowercase()
            key in MOBILE_REMOVED_MODULES ||
                (accountant && !accountantAcademicOverride && key in ACCOUNTANT_DENIED_KEYS) ||
                (key == "staff-attendance" && !canManageStaffAttendance)
        }

        if (portal == "admin") filtered = filtered.filter { adminOperationalModule(it.key) }

        return filtered
            .groupBy { module -> dedupeKey(module.key, portal) }
            .mapNotNull { (_, candidates) ->
                if (portal == "admin") candidates.firstOrNull()
                else candidates.firstOrNull { it.key.equals("staff-attendance.self", true) } ?: candidates.firstOrNull()
            }
    }

    fun canManageStaffAttendance(session: SessionSnapshot): Boolean =
        session.user.portal.equals("admin", true) ||
            session.can("staff_attendance.manage") ||
            session.can("staff-attendance.manage") ||
            session.can("attendance.manage_staff")

    fun canUseSelfAttendance(session: SessionSnapshot): Boolean =
        session.hasModule("staff-attendance.self") || session.can("attendance.self") || session.can("staff_attendance.self")

    fun groupedModules(session: SessionSnapshot): Map<ModuleGroup, List<ModuleDescriptor>> =
        ModuleGroup.entries.mapNotNull { group ->
            val modules = visibleModules(session).filter { groupFor(it) == group }
            if (modules.isEmpty()) null else group to modules
        }.toMap()

    fun groupFor(module: ModuleDescriptor): ModuleGroup {
        val key = module.key.lowercase()
        return when {
            COMMUNICATION_KEYS.any(key::contains) -> ModuleGroup.COMMUNICATION
            SCHEDULE_KEYS.any(key::contains) -> ModuleGroup.SCHEDULE
            ACCOUNT_KEYS.any(key::contains) -> ModuleGroup.ACCOUNT
            ACADEMIC_KEYS.any(key::contains) -> ModuleGroup.ACADEMICS
            else -> ModuleGroup.OPERATIONS
        }
    }

    private fun roleKeys(session: SessionSnapshot) = buildSet {
        add(session.user.roleKey.lowercase())
        session.user.roles.mapTo(this) { it.lowercase() }
    }

    private fun hasExplicitAcademicPermission(session: SessionSnapshot): Boolean =
        session.permissions.any { permission ->
            permission.lowercase() in setOf("*", "classes.view", "classes.manage", "students.view", "student_attendance.manage", "scores.manage", "timetable.view")
        }

    private fun dedupeKey(key: String, portal: String): String {
        val normalized = key.lowercase()
        if (portal == "admin" && normalized in setOf("staff-attendance", "staff-attendance.self")) return normalized
        return canonicalKey(normalized)
    }

    private fun canonicalKey(key: String): String = when (key.lowercase()) {
        "staff-attendance.self" -> "staff-attendance"
        else -> key.lowercase()
    }

    private fun adminOperationalModule(key: String): Boolean {
        val normalized = key.lowercase()
        return normalized in ADMIN_OPERATIONAL_KEYS || ADMIN_OPERATIONAL_PREFIXES.any { normalized.startsWith(it) }
    }

    private val MOBILE_REMOVED_MODULES = setOf(
        "cbt", "cbt-exams", "examinations", "student.exams", "student.cbt", "staff.cbt", "staff-cbt",
        "report-cards", "results", "parent.results", "student.results",
    )
    private val CLASS_WORKSPACE_ENTRY_KEYS = setOf("classes", "students", "attendance", "scores", "scores.entry", "lesson-planner", "academic-repository")
    private val ADMIN_OPERATIONAL_KEYS = setOf(
        "staff", "staff-directory", "classes", "students", "attendance", "student-attendance", "scores", "scores.entry", "subjects",
        "timetable", "reports", "staff-attendance", "staff-attendance.self", "lesson-planner", "academic-repository",
        "messages", "announcements", "notifications.view", "calendar.view", "profile",
    )
    private val ADMIN_OPERATIONAL_PREFIXES = listOf("message", "notification", "announcement", "calendar", "event", "finance", "fee", "invoice", "payment", "expense", "payroll")
    private val ACCOUNTANT_ROLE_KEYS = setOf("accountant", "accounts", "finance", "bursar")
    private val ACCOUNTANT_DENIED_KEYS = setOf("classes", "students", "attendance", "student-attendance", "scores", "scores.entry", "subjects", "lesson-planner", "academic-repository", "timetable")
    private val ACADEMIC_KEYS = listOf("student", "class", "subject", "attendance", "score", "report", "result", "lesson", "repository")
    private val SCHEDULE_KEYS = listOf("timetable", "exam-dut", "schedule")
    private val COMMUNICATION_KEYS = listOf("message", "notice", "notification", "announcement", "calendar", "event", "support", "broadcast")
    private val ACCOUNT_KEYS = listOf("profile", "setting", "help", "payslip")
}
