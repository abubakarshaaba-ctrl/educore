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
        "staff",
        "staff-attendance",
        "staff-attendance.self",
        "timetable",
        "student.timetable",
        "student.exams",
        "cbt",
        "cbt-exams",
        "examinations",
        "academic-repository",
        "lesson-planner",
        "exports",
        "risk",
        "admissions",
        "fees",
        "parent.fees",
        "payroll",
        "expenses",
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
    )

    private val NATIVE_GENERIC_MODULES = setOf(
        "library",
        "transport",
        "health",
        "inventory",
        "hostels",
        "subjects",
        "curriculum",
        "academic-cycle",
        "analytics",
    )

    private val ROLE_CONDITIONAL_MODULES = setOf(
        "scores",
        "scores.entry",
        "results",
        "reports",
        "report-cards",
        "student.results",
        "parent.results",
    )

    // Intentionally empty during the overhaul. A module may be added here only after
    // its web-only status and Laravel destination have been reviewed explicitly.
    private val EXPLICIT_WEB_ONLY_MODULES: Set<String> = emptySet()
}
