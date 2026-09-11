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
        val grouped = visible.groupBy(::groupFor)
        val role = session.user.roleKey.lowercase()
        val hasAcademics = grouped[ModuleGroup.ACADEMICS].orEmpty().isNotEmpty()
        val hasSchedule = grouped[ModuleGroup.SCHEDULE].orEmpty().isNotEmpty()
        val financeRole = role in FINANCE_ROLES

        return when (tab) {
            ShellTabId.HOME -> emptyList()
            ShellTabId.PRIMARY -> when (session.user.portal) {
                "platform" -> visible.filter {
                    it.key.contains("school") || it.key.contains("tenant") || it.key.contains("group")
                }
                "parent" -> visible.filter { it.key.contains("attendance") }
                else -> when {
                    financeRole -> visible.filter { it.key in FINANCE_KEYS }
                    hasAcademics -> grouped[ModuleGroup.ACADEMICS].orEmpty()
                    else -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                }
            }
            ShellTabId.SECONDARY -> when (session.user.portal) {
                "admin", "platform" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                "parent" -> visible.filter {
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
     * Native-app visibility is intentionally stricter than the web permission
     * catalogue. This protects upgraded/offline sessions that may still contain
     * stale module descriptors from older bootstrap responses.
     *
     * Personal attendance is deliberately excluded from tab/module grids and is
     * surfaced as a dedicated Home quick action. School-wide Staff Attendance is
     * exposed on mobile only to the school admin portal.
     */
    fun visibleModules(session: SessionSnapshot): List<ModuleDescriptor> =
        session.modules.filterNot { module ->
            val key = module.key.lowercase()
            isRemovedFromMobile(key) ||
                key == "dashboard" ||
                key == "staff-attendance.self" ||
                (key == "staff-attendance" && !session.user.portal.equals("admin", ignoreCase = true))
        }

    fun isRemovedFromMobile(moduleKey: String): Boolean {
        val key = moduleKey.lowercase()
        return key == "cbt" ||
            key == "cbt-exams" ||
            key == "examinations" ||
            key == "student.exams" ||
            key == "reports" ||
            key == "report-cards" ||
            key == "results" ||
            key == "student.results" ||
            key == "parent.results"
    }

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

    private val FINANCE_ROLES = setOf("accountant", "finance_officer", "bursar")
    private val FINANCE_KEYS = setOf("fees", "expenses", "payroll", "analytics", "exports")
    private val ACADEMIC_KEYS = listOf(
        "student",
        "class",
        "subject",
        "curriculum",
        "attendance",
        "score",
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
