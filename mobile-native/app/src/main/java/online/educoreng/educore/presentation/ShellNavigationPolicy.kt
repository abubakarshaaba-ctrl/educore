package online.educoreng.educore.presentation

import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

enum class ShellTabId(val route: String) {
    HOME("home"),
    PRIMARY("primary"),
    SECONDARY("secondary"),
    INBOX("inbox"),
    MORE("more"),
}

data class ShellTab(
    val id: ShellTabId,
    val label: String,
)

enum class ModuleGroup(val label: String) {
    ACADEMICS("Academics"),
    SCHEDULE("Schedule"),
    OPERATIONS("Operations"),
    COMMUNICATION("Communication"),
    ACCOUNT("Account"),
}

object ShellNavigationPolicy {
    fun tabs(session: SessionSnapshot): List<ShellTab> {
        val portal = session.user.portal
        val role = session.user.roleKey.lowercase()
        val management = portal == "admin" || portal == "platform"
        val modules = visibleModules(session)
        val hasAcademics = modules.any { groupFor(it) == ModuleGroup.ACADEMICS }
        val hasSchedule = modules.any { groupFor(it) == ModuleGroup.SCHEDULE }
        val hasOperations = modules.any { groupFor(it) == ModuleGroup.OPERATIONS }
        val financeRole = role in FINANCE_ROLES

        val primaryLabel = when {
            portal == "parent" -> "Children"
            portal == "platform" -> "Schools"
            financeRole -> "Finance"
            hasAcademics -> if (role.contains("teacher")) "Classes" else "Academics"
            hasOperations -> "Operations"
            else -> "Workspace"
        }
        val secondaryLabel = when {
            management -> "Operations"
            portal == "parent" -> "Academics"
            financeRole -> "Operations"
            hasSchedule -> "Timetable"
            hasOperations -> "Operations"
            else -> "Account"
        }
        val inboxLabel = if (modules.any { it.key.contains("message") || it.key.contains("support") }) {
            "Inbox"
        } else {
            "Notices"
        }

        return listOf(
            ShellTab(ShellTabId.HOME, "Home"),
            ShellTab(ShellTabId.PRIMARY, primaryLabel),
            ShellTab(ShellTabId.SECONDARY, secondaryLabel),
            ShellTab(ShellTabId.INBOX, inboxLabel),
            ShellTab(ShellTabId.MORE, "More"),
        )
    }

    fun modulesFor(tab: ShellTabId, session: SessionSnapshot): List<ModuleDescriptor> {
        val visible = visibleModules(session)
        if (tab == ShellTabId.MORE) return visible

        val tabVisible = visible.filterNot { module ->
            module.key.lowercase() in HUB_ONLY_KEYS ||
                (session.user.portal.equals("admin", ignoreCase = true) && module.key.equals("students", ignoreCase = true))
        }
        val grouped = tabVisible.groupBy(::groupFor)
        val role = session.user.roleKey.lowercase()
        val hasAcademics = grouped[ModuleGroup.ACADEMICS].orEmpty().isNotEmpty()
        val hasSchedule = grouped[ModuleGroup.SCHEDULE].orEmpty().isNotEmpty()
        val financeRole = role in FINANCE_ROLES

        return when (tab) {
            ShellTabId.HOME -> emptyList()
            ShellTabId.PRIMARY -> when (session.user.portal) {
                "platform" -> tabVisible.filter {
                    it.key.contains("school") || it.key.contains("tenant") || it.key.contains("group")
                }
                "parent" -> tabVisible.filter { it.key.contains("attendance") }
                else -> when {
                    financeRole -> tabVisible.filter { it.key in FINANCE_KEYS }
                    hasAcademics -> grouped[ModuleGroup.ACADEMICS].orEmpty()
                    else -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                }
            }
            ShellTabId.SECONDARY -> when (session.user.portal) {
                "admin", "platform" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                "parent" -> tabVisible.filter {
                    groupFor(it) in setOf(ModuleGroup.ACADEMICS, ModuleGroup.OPERATIONS) &&
                        it !in modulesFor(ShellTabId.PRIMARY, session)
                }
                else -> when {
                    financeRole -> grouped[ModuleGroup.OPERATIONS].orEmpty().filterNot { it.key in FINANCE_KEYS }
                    hasSchedule -> grouped[ModuleGroup.SCHEDULE].orEmpty()
                    else -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                }
            }
            ShellTabId.INBOX -> grouped[ModuleGroup.COMMUNICATION].orEmpty()
            ShellTabId.MORE -> visible
        }
    }

    fun groupedModules(session: SessionSnapshot): Map<ModuleGroup, List<ModuleDescriptor>> =
        ModuleGroup.entries.mapNotNull { group ->
            val modules = visibleModules(session).filter { groupFor(it) == group }
            if (modules.isEmpty()) null else group to modules
        }.toMap()

    /**
     * The backend bootstrap module list remains the source of truth for explicit
     * assignments. Mobile applies only hard presentation/role constraints so an
     * explicitly assigned academic module is not hidden from a legitimate user.
     *
     * Personal attendance is deliberately excluded from tab/module grids and is
     * surfaced as a dedicated Home quick action. Legacy `staff-attendance` is a
     * management descriptor only; the personal workspace is always
     * `staff-attendance.self`, while the management workspace is normalized to
     * `staff-attendance.admin`.
     */
    fun visibleModules(session: SessionSnapshot): List<ModuleDescriptor> {
        val financeRole = session.user.roleKey.lowercase() in FINANCE_ROLES
        val canManageStaffAttendance = canManageStaffAttendance(session)

        return session.modules.mapNotNull { module ->
            val key = module.key.trim().lowercase()
            when {
                isRemovedFromMobile(key) -> null
                key == "dashboard" -> null
                key == "staff-attendance.self" -> null
                key == "staff-attendance" && !canManageStaffAttendance -> null
                key == "staff-attendance" -> module.copy(
                    key = "staff-attendance.admin",
                    title = "Staff Attendance",
                )
                key == "staff-attendance.admin" && !canManageStaffAttendance -> null
                financeRole && key in FINANCE_BLOCKED_ACADEMIC_KEYS -> null
                else -> module
            }
        }.distinctBy { it.key.lowercase() }
    }

    fun isRemovedFromMobile(moduleKey: String): Boolean {
        val key = moduleKey.lowercase()
        return key == "cbt" ||
            key == "cbt-exams" ||
            key == "examinations" ||
            key == "student.exams"
    }

    fun groupFor(module: ModuleDescriptor): ModuleGroup {
        val key = module.key.lowercase()
        return when {
            key == "staff-attendance.admin" -> ModuleGroup.OPERATIONS
            COMMUNICATION_KEYS.any(key::contains) -> ModuleGroup.COMMUNICATION
            SCHEDULE_KEYS.any(key::contains) -> ModuleGroup.SCHEDULE
            ACCOUNT_KEYS.any(key::contains) -> ModuleGroup.ACCOUNT
            ACADEMIC_KEYS.any(key::contains) -> ModuleGroup.ACADEMICS
            else -> ModuleGroup.OPERATIONS
        }
    }

    private fun canManageStaffAttendance(session: SessionSnapshot): Boolean {
        if (session.user.portal.equals("admin", ignoreCase = true) ||
            session.user.portal.equals("platform", ignoreCase = true)
        ) return true

        val role = session.user.roleKey
            .trim()
            .lowercase()
            .replace('-', '_')
            .replace(' ', '_')
        return role in STAFF_ATTENDANCE_MANAGEMENT_ROLES
    }

    private val FINANCE_ROLES = setOf("accountant", "finance_officer", "bursar")
    private val STAFF_ATTENDANCE_MANAGEMENT_ROLES = setOf(
        "principal",
        "vice_principal",
        "assistant_principal",
        "head_teacher",
        "head_of_school",
        "school_head",
        "academic_head",
        "assistant_head",
        "vice_principal_academics",
        "vice_principal_administration",
    )
    private val FINANCE_KEYS = setOf("finance", "fees", "expenses", "payroll", "analytics", "exports")
    private val FINANCE_BLOCKED_ACADEMIC_KEYS = setOf(
        "classes",
        "students",
        "attendance",
        "student-attendance",
        "scores",
        "scores.entry",
        "timetable",
        "student.timetable",
        "academic-repository",
        "lesson-planner",
        "gradebook",
        "skills",
        "subjects",
        "curriculum",
        "academic-cycle",
        "reports",
        "report-cards",
        "results",
        "student.results",
        "parent.results",
    )
    private val HUB_ONLY_KEYS = setOf(
        "staff",
        "profile",
        "portal-accounts",
        "settings",
        "skills",
        "transfers",
        "gradebook",
        "risk",
        "exports",
    )
    private val ACADEMIC_KEYS = listOf(
        "student",
        "class",
        "subject",
        "curriculum",
        "attendance",
        "score",
        "result",
        "report",
        "lesson",
        "repository",
        "exam",
    )
    private val SCHEDULE_KEYS = listOf("timetable", "exam-dut", "schedule")
    private val COMMUNICATION_KEYS = listOf(
        "message",
        "notice",
        "notification",
        "announcement",
        "calendar",
        "event",
        "support",
        "broadcast",
    )
    private val ACCOUNT_KEYS = listOf("profile", "setting", "help")
}
