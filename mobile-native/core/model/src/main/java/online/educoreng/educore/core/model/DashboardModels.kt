package online.educoreng.educore.core.model

data class DashboardMetric(
    val key: String,
    val label: String,
    val displayValue: String,
    val tone: String,
    val moduleKey: String?,
)

data class DashboardAction(
    val moduleKey: String,
    val title: String,
    val icon: String,
    val path: String,
)

data class DashboardItem(
    val id: String,
    val title: String,
    val subtitle: String?,
    val supportingText: String?,
    val status: String?,
    val timestamp: String?,
    val moduleKey: String?,
)

data class DashboardSection(
    val key: String,
    val title: String,
    val items: List<DashboardItem>,
)

data class DashboardSnapshot(
    val scope: String,
    val roleKey: String,
    val generatedAt: String,
    val metrics: List<DashboardMetric>,
    val quickActions: List<DashboardAction>,
    val sections: List<DashboardSection>,
    val contractVersion: Int = 1,
    val cachedAtEpochMs: Long? = null,
    val isFromCache: Boolean = false,
)
