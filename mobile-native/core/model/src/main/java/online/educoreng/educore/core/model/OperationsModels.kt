package online.educoreng.educore.core.model

data class OperationsModule(
    val key: String,
    val title: String,
    val description: String,
    val canManage: Boolean,
    val mobilePolicy: String,
)

data class OperationsMetric(
    val key: String,
    val label: String,
    val value: String,
    val format: String,
    val tone: String,
)

data class OperationsField(val label: String, val value: String)

data class OperationsRecord(
    val id: String,
    val title: String,
    val subtitle: String?,
    val status: String?,
    val fields: List<OperationsField>,
)

data class OperationsSection(
    val key: String,
    val title: String,
    val count: Int,
    val records: List<OperationsRecord>,
)

data class OperationsWorkspace(
    val module: OperationsModule,
    val metrics: List<OperationsMetric>,
    val sections: List<OperationsSection>,
    val generatedAt: String,
)
