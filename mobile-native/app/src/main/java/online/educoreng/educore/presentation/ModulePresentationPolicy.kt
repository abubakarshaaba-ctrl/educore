package online.educoreng.educore.presentation

enum class ModulePresentation {
    NATIVE,
    NATIVE_GENERIC,
    ROLE_CONDITIONAL,
    WEB_ONLY,
    UNSUPPORTED,
}

object ModulePresentationPolicy {
    fun presentationFor(key: String): ModulePresentation {
        val normalized = key.trim().lowercase()

        return when {
            normalized in NATIVE_MODULES -> ModulePresentation.NATIVE
            normalized in NATIVE_GENERIC_MODULES -> ModulePresentation.NATIVE_GENERIC
            normalized in ROLE_CONDITIONAL_MODULES -> ModulePresentation.ROLE_CONDITIONAL
            normalized in EXPLICIT_WEB_ONLY_MODULES -> ModulePresentation.WEB_ONLY
            else -> ModulePresentation.UNSUPPORTED
        }
    }

    fun allowsBrowserHandoff(key: String): Boolean =
        presentationFor(key) == ModulePresentation.WEB_ONLY

    private val NATIVE_MODULES = setOf(
        "classes",
        "students",
        "attendance",
        "student-attendance",
        "staff",
        "staff-attendance",
        "staff-attendance.self",
        "staff-attendance.admin",
        "skills",
        "transfers",
        "portal-accounts",
        "gradebook",
        "reports",
        "report-cards",
        "results",
        "student.results",
        "parent.results",
        "timetable",
        "student.timetable",
        "academic-repository",
        "lesson-planner",
        "exports",
        "risk",
        "admissions",
        "fees",
        "parent.fees",
        "payroll",
        "expenses",
        "library",
        "transport",
        "health",
        "inventory",
        "hostels",
        "subjects",
        "curriculum",
        "academic-cycle",
        "messages",
        "parent.messages",
        "student.messages",
        "notifications.view",
        "parent.notifications",
        "student.notifications",
        "calendar.view",
        "parent.calendar",
        "student.calendar",
        "announcements",
        "profile",
        "settings",
    )

    private val NATIVE_GENERIC_MODULES: Set<String> = setOf(
        "finance",
        "analytics",
    )

    private val ROLE_CONDITIONAL_MODULES = setOf(
        "scores",
        "scores.entry",
    )

    // Normal visible mobile modules never fall through to the browser. A future
    // intentionally web-only capability must be added here explicitly.
    private val EXPLICIT_WEB_ONLY_MODULES: Set<String> = emptySet()
}
