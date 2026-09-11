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
            normalized in MOBILE_REMOVED_MODULES -> ModulePresentation.UNSUPPORTED
            normalized in NATIVE_MODULES -> ModulePresentation.NATIVE
            normalized in NATIVE_GENERIC_MODULES -> ModulePresentation.NATIVE_GENERIC
            normalized in ROLE_CONDITIONAL_MODULES -> ModulePresentation.ROLE_CONDITIONAL
            normalized in EXPLICIT_WEB_ONLY_MODULES -> ModulePresentation.WEB_ONLY
            else -> ModulePresentation.UNSUPPORTED
        }
    }

    fun allowsBrowserHandoff(key: String): Boolean =
        presentationFor(key) == ModulePresentation.WEB_ONLY

    /** Modules intentionally absent from the Android product, even if a stale bootstrap advertises them. */
    private val MOBILE_REMOVED_MODULES = setOf(
        "cbt",
        "cbt-exams",
        "examinations",
        "student.exams",
        "student.cbt",
        "staff.cbt",
        "student.results",
    )

    private val NATIVE_MODULES = setOf(
        "classes",
        "students",
        "attendance",
        "staff",
        "staff-attendance",
        "staff-attendance.self",
        "skills",
        "transfers",
        "portal-accounts",
        "gradebook",
        "reports",
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
    )

    private val NATIVE_GENERIC_MODULES: Set<String> = setOf(
        "analytics",
    )

    /** Result/report-card rendering is allowed only after the shell's parent-portal gate. */
    private val ROLE_CONDITIONAL_MODULES = setOf(
        "scores",
        "scores.entry",
        "results",
        "report-cards",
        "parent.results",
    )

    private val EXPLICIT_WEB_ONLY_MODULES: Set<String> = emptySet()
}
