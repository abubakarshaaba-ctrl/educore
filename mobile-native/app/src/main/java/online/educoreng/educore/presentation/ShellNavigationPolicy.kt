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
        val modules = visibleModules(session)

        val primaryLabel = when {
            portal == "parent" -> "Children"
            portal == "platform" -> "Schools"
            role.contains("teacher") || portal == "staff" -> "Classes"
            else -> "Academics"
        }
        val secondaryLabel = when {
            management -> "Operations"
            portal == "parent" -> "Academics"
            else -> "Timetable"
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
        return when (tab) {
            ShellTabId.HOME -> emptyList()
            ShellTabId.PRIMARY -> when (session.user.portal) {
                "platform" -> visible.filter {
                    it.key.contains("school") || it.key.contains("tenant") || it.key.contains("group")
                }
                "parent" -> visible.filter { it.key.contains("attendance") }
                else -> grouped[ModuleGroup.ACADEMICS].orEmpty()
            }
            ShellTabId.SECONDARY -> when (session.user.portal) {
                "admin", "platform" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                "parent" -> visible.filter {
                    groupFor(it) in setOf(ModuleGroup.ACADEMICS, ModuleGroup.OPERATIONS) &&
                        it !in modulesFor(ShellTabId.PRIMARY, session)
                }
                else -> grouped[ModuleGroup.SCHEDULE].orEmpty()
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
     */
    fun visibleModules(session: SessionSnapshot): List<ModuleDescriptor> =
        session.modules.filterNot { module -> isRemovedFromMobile(module.key) }

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
