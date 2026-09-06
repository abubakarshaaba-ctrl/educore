package online.educoreng.educore.core.model

import java.util.UUID

data class RepositorySubjectGroup(val name: String, val resourceCount: Int)
data class RepositoryTermGroup(val name: String, val resourceCount: Int, val subjects: List<RepositorySubjectGroup>)
data class RepositoryClassGroup(val name: String, val resourceCount: Int, val terms: List<RepositoryTermGroup>)
data class RepositoryMetrics(val resources: Int, val classes: Int, val subjects: Int, val sections: Int)
data class RepositoryHierarchy(
    val metrics: RepositoryMetrics,
    val classes: List<RepositoryClassGroup>,
    val generatedAt: String,
    val isFromCache: Boolean = false,
)

data class RepositoryFilters(
    val classes: List<String> = emptyList(),
    val terms: List<String> = emptyList(),
    val subjects: List<String> = emptyList(),
)

data class RepositoryResource(
    val id: Long,
    val title: String,
    val filename: String?,
    val mimeType: String?,
    val fileSize: Long?,
    val checksum: String?,
    val className: String,
    val term: String,
    val subject: String,
    val authority: String?,
    val sourceType: String?,
    val version: String?,
    val fragmentsCount: Int,
    val updatedAt: String?,
)

data class RepositoryFragment(
    val id: Long,
    val sequence: Int,
    val theme: String?,
    val topic: String,
    val subtopic: String?,
    val content: String,
    val learningExpectation: String?,
    val sourceLocator: String?,
)

data class RepositoryResourceDetail(
    val resource: RepositoryResource,
    val fragments: List<RepositoryFragment>,
    val generatedAt: String,
    val isFromCache: Boolean = false,
)

data class RepositoryCatalogue(
    val filters: RepositoryFilters,
    val resources: List<RepositoryResource>,
    val generatedAt: String,
    val isFromCache: Boolean = false,
    val currentPage: Int = 1,
    val lastPage: Int = 1,
    val total: Int = resources.size,
)

data class LessonSubjectOption(val id: Long, val name: String)
data class LessonAssignmentOption(
    val classArmId: Long,
    val classLevelId: Long,
    val className: String,
    val subjects: List<LessonSubjectOption>,
)
data class LessonTermOption(val id: Long, val name: String, val session: String?, val isCurrent: Boolean)
data class KeyLabelOption(val key: String, val label: String)
data class LessonPlannerOptions(
    val assignments: List<LessonAssignmentOption>,
    val terms: List<LessonTermOption>,
    val curriculumTypes: List<KeyLabelOption>,
    val deliveryTypes: List<KeyLabelOption>,
    val generatedAt: String,
    val isFromCache: Boolean = false,
)

data class LessonPlanSection(val key: String, val label: String, val content: String?)
data class LessonNote(
    val revision: Int,
    val status: String,
    val depth: String,
    val html: String?,
    val plainText: String,
    val teacherEdited: Boolean,
)

data class LessonPlan(
    val id: Long,
    val version: String,
    val subject: LessonSubjectOption?,
    val classLevel: LessonSubjectOption?,
    val classArm: LessonSubjectOption?,
    val term: LessonSubjectOption?,
    val curriculumType: String,
    val curriculumLevelId: Long?,
    val deliveryType: String,
    val topic: String,
    val subtopic: String?,
    val weekNumber: Int?,
    val lessonNumber: String?,
    val lessonTime: String?,
    val averageAge: String?,
    val sex: String?,
    val planDate: String?,
    val durationMinutes: Int,
    val status: String,
    val aiGenerated: Boolean,
    val hasNote: Boolean,
    val publishedAt: String?,
    val updatedAt: String?,
    val sections: List<LessonPlanSection> = emptyList(),
    val note: LessonNote? = null,
)

data class LessonPlanDraft(
    val requestId: String = UUID.randomUUID().toString(),
    val remoteId: Long? = null,
    val version: String? = null,
    val classArmId: Long? = null,
    val classLevelId: Long? = null,
    val subjectId: Long? = null,
    val termId: Long? = null,
    val curriculumType: String = "nerdc",
    val curriculumLevelId: Long? = null,
    val deliveryType: String = "regular",
    val topic: String = "",
    val subtopic: String = "",
    val weekNumber: String = "",
    val lessonNumber: String = "",
    val lessonTime: String = "",
    val averageAge: String = "",
    val sex: String = "Mixed",
    val planDate: String = "",
    val durationMinutes: String = "40",
    val status: String = "draft",
    val sections: Map<String, String> = emptyMap(),
    val updatedAtEpochMs: Long = System.currentTimeMillis(),
)

data class DownloadedDocument(val uri: String, val filename: String, val mimeType: String)
