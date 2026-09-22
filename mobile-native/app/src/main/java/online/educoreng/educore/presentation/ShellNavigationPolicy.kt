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
        val portal = session.user.portal.trim().lowercase()
        val role = normalizedRole(session)
        val modules = visibleModules(session)
        val hasAcademics = modules.any { groupFor(it) == ModuleGroup.ACADEMICS }
        val hasSchedule = modules.any { groupFor(it) == ModuleGroup.SCHEDULE }
        val hasOperations = modules.any { groupFor(it) == ModuleGroup.OPERATIONS }
        val financeRole = role in FINANCE_ROLES

        val primaryLabel = when {
            portal == "platform" -> "Schools"
            portal == "parent" -> "Children"
            portal == "student" -> "Academics"
            financeRole -> "Finance"
            portal == "admin" -> if (hasAcademics) "Academics" else "Operations"
            isTeachingRole(role) -> "Classes"
            hasAcademics -> "Academics"
            hasOperations -> "Operations"
            else -> "Workspace"
        }
        val secondaryLabel = when {
            portal == "platform" -> "Operations"
            portal == "parent" -> "Academics"
            portal == "student" && hasSchedule -> "Timetable"
            portal == "admin" -> "Operations"
            financeRole -> "Operations"
            isTeachingRole(role) && hasSchedule -> "Timetable"
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

        val portal = session.user.portal.trim().lowercase()
        val tabVisible = visible.filterNot { module ->
            module.key.lowercase() in HUB_ONLY_KEYS ||
                (portal == "admin" && module.key.equals("students", ignoreCase = true))
        }
        val grouped = tabVisible.groupBy(::groupFor)
        val role = normalizedRole(session)
        val hasSchedule = grouped[ModuleGroup.SCHEDULE].orEmpty().isNotEmpty()
        val financeRole = role in FINANCE_ROLES

        fun primaryModules(): List<ModuleDescriptor> = when (portal) {
            "platform" -> tabVisible.filter { module ->
                val key = module.key.lowercase()
                key.contains("school") || key.contains("tenant") || key.contains("group")
            }
            "parent" -> tabVisible.filter { it.key.lowercase().contains("attendance") }
            "student" -> grouped[ModuleGroup.ACADEMICS].orEmpty()
            "admin" -> grouped[ModuleGroup.ACADEMICS].orEmpty()
            else -> when {
                financeRole -> tabVisible.filter { it.key.lowercase() in FINANCE_KEYS }
                isTeachingRole(role) -> grouped[ModuleGroup.ACADEMICS].orEmpty()
                grouped[ModuleGroup.ACADEMICS].orEmpty().isNotEmpty() ->
                    grouped[ModuleGroup.ACADEMICS].orEmpty()
                else -> grouped[ModuleGroup.OPERATIONS].orEmpty()
            }
        }

        return when (tab) {
            ShellTabId.HOME -> emptyList()
            ShellTabId.PRIMARY -> primaryModules()
            ShellTabId.SECONDARY -> when (portal) {
                "platform" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                    .filterNot { it in primaryModules() }
                "admin" -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                "parent" -> tabVisible.filter {
                    groupFor(it) in setOf(ModuleGroup.ACADEMICS, ModuleGroup.OPERATIONS) &&
                        it !in primaryModules()
                }
                "student" -> if (hasSchedule) {
                    grouped[ModuleGroup.SCHEDULE].orEmpty()
                } else {
                    grouped[ModuleGroup.OPERATIONS].orEmpty()
                }
                else -> when {
                    financeRole -> grouped[ModuleGroup.OPERATIONS].orEmpty()
                        .filterNot { it.key.lowercase() in FINANCE_KEYS }
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

        val visible = session.modules.mapNotNull { module ->
            val sourceKey = module.key.trim().lowercase()
            val key = canonicalModuleKey(sourceKey, portal)
            when {
                isRemovedFromMobile(sourceKey) -> null
                sourceKey == "dashboard" -> null
                !isExplicitlyAuthorizedModule(session, sourceKey, key) -> null
                !isAllowedForSpecialistRole(role, key) -> null
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
                else -> module.copy(title = canonicalTitle(key, module.title))
            }
        }.toMutableList()

        // School administrators always retain the native Report Cards workspace.
        // The backend already authorises this workspace, but this defensive fallback
        // protects older/stale bootstrap payloads from hiding it in the Android shell.
        if (portal == "admin" && visible.none { it.key.equals("reports", ignoreCase = true) }) {
            visible += ModuleDescriptor(
                key = "reports",
                title = "Report Cards",
                path = "/reports",
                icon = "reports",
            )
        }

        // Score Entry is a core native academic workspace. Trust an explicit
        // server-provided scores module, and also recover it for known academic
        // staff roles when an older/stale bootstrap payload omits the descriptor.
        if (
            portal in setOf("staff", "admin") &&
            isAllowedForSpecialistRole(role, "scores") &&
            (portal == "admin" || role in SCORE_ENTRY_ROLE_KEYS || hasAnyPermission(session, SCORE_PERMISSION_KEYS)) &&
            visible.none { it.key.equals("scores", ignoreCase = true) }
        ) {
            visible += ModuleDescriptor(
                key = "scores",
                title = "Score Entry",
                path = "/scores",
                icon = "scores",
            )
        }

        // Self staff attendance is a native workspace and supports offline clock-in.
        // Keep it visible whenever the backend grants the self-attendance permission,
        // including stale bootstrap payloads where the module descriptor is missing.
        if (
            portal == "staff" &&
            session.can("staff-attendance.self") &&
            visible.none { it.key.equals("staff-attendance.self", ignoreCase = true) }
        ) {
            visible += ModuleDescriptor(
                key = "staff-attendance.self",
                title = "My Attendance",
                path = "/staff-attendance",
                icon = "staff-attendance",
            )
        }

        return visible.distinctBy { it.key.lowercase() }
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

    private fun isExplicitlyAuthorizedModule(
        session: SessionSnapshot,
        sourceKey: String,
        canonicalKey: String,
    ): Boolean {
        val portal = session.user.portal.trim().lowercase()

        // The bootstrap module list is already server/RBAC filtered. Do not remove
        // a server-authorized Score Entry descriptor merely because an equivalent
        // permission alias is absent from the separate permissions array.
        if (canonicalKey == "scores") return true

        // Admin, platform, student and parent portals likewise trust the server's
        // already-filtered module contract.
        if (portal in setOf("admin", "platform", "student", "parent")) return true

        if (session.permissions.contains("*")) return true
        if (sourceKey == "profile" || canonicalKey == "profile") return true

        if (sourceKey in setOf("staff-attendance.admin", "staff-attendance")) {
            return session.can("staff-attendance")
        }
        if (sourceKey == "staff-attendance.self") {
            return session.can("staff-attendance.self")
        }

        if (canonicalKey == "academic-repository") {
            val role = normalizedRole(session)
            return role.contains("teacher") || hasAnyPermission(session, setOf("academic-repository"))
        }

        if (canonicalKey == "reports") {
            return hasAnyPermission(session, REPORT_PERMISSION_KEYS)
        }

        return hasAnyPermission(session, setOf(sourceKey, canonicalKey))
    }

    private fun hasAnyPermission(session: SessionSnapshot, keys: Set<String>): Boolean =
        keys.any { key ->
            session.can(key) || session.permissions.any { permission ->
                permission.startsWith("$key.") || key.startsWith("$permission.")
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
            key in setOf("staff-attendance.admin", "staff-attendance.self") -> ModuleGroup.OPERATIONS
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

    private fun isTeachingRole(role: String): Boolean =
        role.contains("teacher") || role in TEACHING_ROLES

    private val FINANCE_ROLES = setOf("accountant", "finance_officer", "bursar")
    private val TEACHING_ROLES = setOf("hod", "head_of_department", "director_of_studies")
    private val FINANCE_KEYS = setOf("finance", "fees", "expenses", "payroll", "analytics", "exports")
    private val STAFF_REPORT_ALIASES = setOf("reports", "report-cards", "report_cards", "results")
    private val PUBLISHED_RESULT_ALIASES = setOf(
        "results", "report-cards", "report_cards", "reports", "student.results", "parent.results",
    )
    private val SCORE_ALIASES = setOf(
        "scores", "scores.entry", "score-entry", "score_entry", "result-entry", "result_entry",
    )
    private val SCORE_PERMISSION_KEYS = SCORE_ALIASES + setOf("scores.view", "scores.manage")
    private val SCORE_ENTRY_ROLE_KEYS = setOf(
        "teacher",
        "subject_teacher",
        "class_teacher",
        "form_teacher",
        "asst_form_teacher",
        "form_subject_teacher",
        "hod",
        "head_of_department",
        "director_of_studies",
        "principal",
        "vice_principal",
        "vice_principal_academics",
        "assistant_principal",
        "assistant_head",
        "academic_head",
        "academic_administrator",
        "head",
        "head_teacher",
        "head_of_school",
        "school_head",
    )
    private val REPORT_PERMISSION_KEYS = STAFF_REPORT_ALIASES + setOf("reports.view", "reports.manage")
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
