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
        val role = normalizedRole(session)
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
        val role = normalizedRole(session)
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

    fun visibleModules(session: SessionSnapshot): List<ModuleDescriptor> {
        val role = normalizedRole(session)
        val portal = session.user.portal.trim().lowercase()
        val financeRole = role in FINANCE_ROLES
        val canManageStaffAttendance = canManageStaffAttendance(session)

        return session.modules.mapNotNull { module ->
            val sourceKey = module.key.trim().lowercase()
            val key = canonicalModuleKey(sourceKey, portal)
            when {
                isRemovedFromMobile(sourceKey) -> null
                sourceKey == "dashboard" -> null
                !isExplicitlyAuthorizedModule(session, sourceKey) -> null
                !isAllowedForSpecialistRole(role, key) -> null
                sourceKey == "staff-attendance.self" -> null
                sourceKey == "staff-attendance" && !canManageStaffAttendance -> null
                sourceKey == "staff-attendance" -> module.copy(
                    key = "staff-attendance.admin",
                    title = "Staff Attendance",
                )
                sourceKey == "staff-attendance.admin" && !canManageStaffAttendance -> null
                financeRole && key in FINANCE_BLOCKED_ACADEMIC_KEYS -> null
                key != sourceKey -> module.copy(
                    key = key,
                    title = canonicalTitle(key, module.title),
                )
                else -> module
            }
        }.distinctBy { it.key.lowercase() }
    }

    private fun canonicalModuleKey(key: String, portal: String): String = when {
        portal in setOf("staff", "admin") && key in STAFF_REPORT_ALIASES -> "reports"
        portal == "parent" && key in PUBLISHED_RESULT_ALIASES -> "parent.results"
        portal == "student" && key in PUBLISHED_RESULT_ALIASES -> "student.results"
        key in SCORE_ALIASES -> "scores"
        key in SCHEDULE_ALIASES -> "timetable"
        key in COMMUNICATION_ALIASES -> COMMUNICATION_ALIASES.getValue(key)
        else -> key
    }

    private fun canonicalTitle(key: String, fallback: String): String = when (key) {
        "reports" -> "Report Cards"
        "parent.results", "student.results" -> "Results"
        "scores" -> "Score Entry"
        "timetable" -> "Exam Timetable & Supervision"
        "messages" -> "Messages"
        "announcements" -> "Notices"
        "calendar.view" -> "Events"
        else -> fallback
    }

    private fun isAllowedForSpecialistRole(role: String, key: String): Boolean {
        val allowed = SPECIALIST_ROLE_ALLOWED_KEYS[role] ?: return true
        return key in allowed || COMMUNICATION_ALLOWED_KEYS.any { communicationKey ->
            key == communicationKey || key.startsWith("$communicationKey.")
        }
    }

    private fun isExplicitlyAuthorizedModule(session: SessionSnapshot, key: String): Boolean {
        if (session.user.portal.equals("platform", ignoreCase = true) ||
            session.user.portal.equals("student", ignoreCase = true) ||
            session.user.portal.equals("parent", ignoreCase = true)
        ) return true

        if (session.user.portal.equals("admin", ignoreCase = true) &&
            normalizedRole(session) == "admin" && key == "subscription"
        ) return true

        if (session.permissions.contains("*")) return true
        if (key == "profile") return true

        if (key == "staff-attendance.admin" || key == "staff-attendance") {
            return session.can("staff-attendance")
        }
        if (key == "staff-attendance.self") {
            return session.can("staff-attendance.self")
        }

        if (key == "academic-repository") {
            val role = normalizedRole(session)
            return session.user.portal.equals("admin", ignoreCase = true) || role.contains("teacher")
        }

        return session.can(key) || session.permissions.any { permission ->
            permission.startsWith("$key.")
        }
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
        if (session.user.portal.equals("platform", ignoreCase = true)) return true
        if (!session.can("staff-attendance")) return false

        val role = normalizedRole(session)
        return session.user.portal.equals("admin", ignoreCase = true) || role in STAFF_ATTENDANCE_MANAGEMENT_ROLES
    }

    private fun normalizedRole(session: SessionSnapshot): String = session.user.roleKey
        .trim()
        .lowercase()
        .replace('-', '_')
        .replace(' ', '_')

    private val FINANCE_ROLES = setOf("accountant", "finance_officer", "bursar")
    private val FINANCE_KEYS = setOf("finance", "fees", "expenses", "payroll", "analytics", "exports")
    private val STAFF_REPORT_ALIASES = setOf("reports", "report-cards", "report_cards", "results")
    private val PUBLISHED_RESULT_ALIASES = setOf(
        "results", "report-cards", "report_cards", "reports", "student.results", "parent.results",
    )
    private val SCORE_ALIASES = setOf(
        "scores", "scores.entry", "score-entry", "score_entry", "result-entry", "result_entry",
    )
    private val SCHEDULE_ALIASES = setOf(
        "timetable", "student.timetable", "exam-timetable", "exam_timetable", "exam.timetable",
        "exam-duty", "exam_duty", "exam-duties", "exam_duties", "exam-dut", "supervision-schedule",
        "supervision_schedule", "supervision.schedule", "exam-supervision", "exam_supervision", "schedule",
    )
    private val COMMUNICATION_ALIASES = mapOf(
        "communications" to "messages",
        "communication" to "messages",
        "notices" to "announcements",
        "notice" to "announcements",
        "events" to "calendar.view",
        "calendar" to "calendar.view",
        "platform-broadcast" to "announcements",
        "platform.broadcast" to "announcements",
        "broadcast" to "announcements",
    )
    private val COMMUNICATION_ALLOWED_KEYS = setOf(
        "messages", "notifications", "notifications.view", "announcements", "calendar", "calendar.view",
        "support", "profile",
    )
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
        "academic_administrator",
        "head",
    )
    private val SPECIALIST_ROLE_ALLOWED_KEYS = mapOf(
        "admission_officer" to setOf("admissions", "profile"),
        "admissions_officer" to setOf("admissions", "profile"),
        "transport_officer" to setOf("transport", "profile"),
        "transport_manager" to setOf("transport", "profile"),
        "health_officer" to setOf("health", "profile"),
        "school_nurse" to setOf("health", "profile"),
        "nurse" to setOf("health", "profile"),
        "communication_officer" to setOf("profile"),
        "communications_officer" to setOf("profile"),
        "accountant" to FINANCE_KEYS + "profile",
        "finance_officer" to FINANCE_KEYS + "profile",
        "bursar" to FINANCE_KEYS + "profile",
    )
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
        "reports",
        "report-cards",
        "results",
        "student.results",
        "parent.results",
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
    private val SCHEDULE_KEYS = listOf("timetable", "exam-dut", "supervision", "schedule")
    private val COMMUNICATION_KEYS = listOf(
        "message",
        "notice",
        "notification",
        "announcement",
        "calendar",
        "event",
        "support",
        "broadcast",
        "communication",
    )
    private val ACCOUNT_KEYS = listOf("profile", "setting", "help")
}
