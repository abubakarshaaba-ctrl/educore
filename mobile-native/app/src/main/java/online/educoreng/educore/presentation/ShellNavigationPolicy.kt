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
        val role = session.user.roleKey
        val management = portal == "admin" || portal == "platform"

        val primaryLabel = when {
            portal == "parent" -> "Children"
            portal == "platform" -> "Schools"
            role.contains("teacher") || portal in setOf("staff", "admin") -> "Classes"
            else -> "Academics"
        }
        val secondaryLabel = when {
            management -> "Operations"
            portal == "parent" -> "Academics"
            else -> "Timetable"
        }
        val inboxLabel = if (visibleModules(session).any { it.key.contains("message") || it.key.contains("support") }) {
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
        val modules = visibleModules(session)
        val grouped = modules.groupBy(::groupFor)
        return when (tab) {
            ShellTabId.HOME -> emptyList()
            ShellTabId.PRIMARY -> when (session.user.portal) {
                "platform" -> modules.filter {
                    it.key.contains("school") || it.key.contains("tenant") || it.key.contains("group")
                }
                "parent" -> modules.filter {
                    it.key.contains("result") || it.key.contains("attendance")
                }
                "staff", "admin" -> modules.filter { it.key.lowercase() in CLASS_WORKSPACE_ENTRY_KEYS }
                else -> grouped[ModuleGroup.ACADEMICS].orEmpty()
            }
            ShellTabId.SECONDARY -> when (session.user.portal) {
                "admin", "platform" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                "parent" -> modules.filter {
                    groupFor(it) in setOf(ModuleGroup.ACADEMICS, ModuleGroup.OPERATIONS) &&
                        it !in modulesFor(ShellTabId.PRIMARY, session)
                }
                else -> grouped[ModuleGroup.SCHEDULE].orEmpty()
            }
            ShellTabId.INBOX -> grouped[ModuleGroup.COMMUNICATION].orEmpty()
            ShellTabId.MORE -> {
                val alreadyPlaced = (
                    modulesFor(ShellTabId.PRIMARY, session) +
                        modulesFor(ShellTabId.SECONDARY, session) +
                        modulesFor(ShellTabId.INBOX, session)
                    ).map { canonicalKey(it.key) }.toSet()
                modules.filterNot { canonicalKey(it.key) in alreadyPlaced }
            }
        }
    }

    /**
     * Server bootstrap remains authoritative. This client-side layer is a
     * fail-closed safety net for stale bootstrap payloads and removes duplicate
     * aliases from navigation; it never grants a module that the server omitted.
     *
     * School administrators use the same operational shell as staff. Their
     * native scope is deliberately limited to day-to-day school operations;
     * full school configuration remains web-only.
     */
    fun visibleModules(session: SessionSnapshot): List<ModuleDescriptor> {
        val roleKeys = buildSet {
            add(session.user.roleKey.lowercase())
            session.user.roles.mapTo(this) { it.lowercase() }
        }
        val portal = session.user.portal.lowercase()
        val isAccountant = roleKeys.any { it in ACCOUNTANT_ROLE_KEYS }

        var filtered = session.modules.filterNot { module ->
            isAccountant && module.key.lowercase() in ACCOUNTANT_DENIED_KEYS
        }

        if (portal == "admin") {
            filtered = filtered.filter { module -> adminOperationalModule(module.key) }
        }

        val hasSelfAttendance = filtered.any { it.key.equals("staff-attendance.self", ignoreCase = true) }
        val attendanceAware = if (portal == "admin") {
            // Administrators need both their own attendance and the staff report.
            filtered
        } else {
            filtered.filterNot { module ->
                hasSelfAttendance && module.key.equals("staff-attendance", ignoreCase = true)
            }
        }

        return attendanceAware
            .groupBy { module -> dedupeKey(module.key, portal) }
            .mapNotNull { (_, candidates) ->
                if (portal == "admin") {
                    candidates.firstOrNull()
                } else {
                    candidates.firstOrNull { it.key.equals("staff-attendance.self", ignoreCase = true) }
                        ?: candidates.firstOrNull()
                }
            }
    }

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

    private fun dedupeKey(key: String, portal: String): String {
        val normalized = key.lowercase()
        if (portal == "admin" && normalized in setOf("staff-attendance", "staff-attendance.self")) {
            return normalized
        }
        return canonicalKey(normalized)
    }

    private fun canonicalKey(key: String): String = when (key.lowercase()) {
        "staff-attendance.self" -> "staff-attendance"
        "report-cards", "results" -> "reports"
        "cbt-exams", "examinations" -> "cbt"
        else -> key.lowercase()
    }

    private fun adminOperationalModule(key: String): Boolean {
        val normalized = key.lowercase()
        return normalized in ADMIN_OPERATIONAL_KEYS ||
            ADMIN_OPERATIONAL_PREFIXES.any { normalized.startsWith(it) }
    }

    private val CLASS_WORKSPACE_ENTRY_KEYS = setOf(
        "classes",
        "students",
        "attendance",
        "scores",
        "scores.entry",
        "lesson-planner",
        "academic-repository",
    )

    private val ADMIN_OPERATIONAL_KEYS = setOf(
        "classes",
        "students",
        "attendance",
        "student-attendance",
        "scores",
        "scores.entry",
        "subjects",
        "timetable",
        "reports",
        "report-cards",
        "results",
        "staff-attendance",
        "staff-attendance.self",
        "cbt",
        "cbt-exams",
        "examinations",
        "lesson-planner",
        "academic-repository",
        "messages",
        "announcements",
        "notifications.view",
        "calendar.view",
        "profile",
    )

    private val ADMIN_OPERATIONAL_PREFIXES = listOf(
        "message",
        "notification",
        "announcement",
        "calendar",
        "event",
    )

    private val ACCOUNTANT_ROLE_KEYS = setOf("accountant", "accounts", "finance", "bursar")
    private val ACCOUNTANT_DENIED_KEYS = setOf(
        "attendance",
        "student-attendance",
        "scores",
        "scores.entry",
        "subjects",
    )

    private val ACADEMIC_KEYS = listOf(
        "student", "class", "subject", "attendance", "score",
        "report", "result", "lesson", "repository", "cbt", "exam",
    )
    private val SCHEDULE_KEYS = listOf("timetable", "exam-dut", "schedule")
    private val COMMUNICATION_KEYS = listOf(
        "message", "notice", "notification", "announcement", "calendar",
        "event", "support", "broadcast",
    )
    private val ACCOUNT_KEYS = listOf("profile", "setting", "help")
}
