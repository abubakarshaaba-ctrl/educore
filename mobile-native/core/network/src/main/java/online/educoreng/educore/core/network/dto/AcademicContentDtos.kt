package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.KeyLabelOption
import online.educoreng.educore.core.model.LessonAssignmentOption
import online.educoreng.educore.core.model.LessonNote
import online.educoreng.educore.core.model.LessonPlan
import online.educoreng.educore.core.model.LessonPlanSection
import online.educoreng.educore.core.model.LessonPlannerOptions
import online.educoreng.educore.core.model.LessonSubjectOption
import online.educoreng.educore.core.model.LessonTermOption
import online.educoreng.educore.core.model.RepositoryCatalogue
import online.educoreng.educore.core.model.RepositoryClassGroup
import online.educoreng.educore.core.model.RepositoryFilters
import online.educoreng.educore.core.model.RepositoryFragment
import online.educoreng.educore.core.model.RepositoryHierarchy
import online.educoreng.educore.core.model.RepositoryMetrics
import online.educoreng.educore.core.model.RepositoryResource
import online.educoreng.educore.core.model.RepositoryResourceDetail
import online.educoreng.educore.core.model.RepositorySubjectGroup
import online.educoreng.educore.core.model.RepositoryTermGroup

data class RepositoryMetricsDto(val resources: Int, val classes: Int, val subjects: Int, val sections: Int)
data class RepositorySubjectGroupDto(val name: String, @param:Json(name = "resources_count") val resourceCount: Int)
data class RepositoryTermGroupDto(
    val name: String,
    @param:Json(name = "resources_count") val resourceCount: Int,
    val subjects: List<RepositorySubjectGroupDto> = emptyList(),
)
data class RepositoryClassGroupDto(
    val name: String,
    @param:Json(name = "resources_count") val resourceCount: Int,
    val terms: List<RepositoryTermGroupDto> = emptyList(),
)
data class RepositoryHierarchyResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val metrics: RepositoryMetricsDto,
    val classes: List<RepositoryClassGroupDto> = emptyList(),
)
data class RepositoryFiltersDto(
    val classes: List<String> = emptyList(),
    val terms: List<String> = emptyList(),
    val subjects: List<String> = emptyList(),
)
data class RepositoryResourceDto(
    val id: Long,
    val title: String,
    val filename: String? = null,
    @param:Json(name = "mime_type") val mimeType: String? = null,
    @param:Json(name = "file_size") val fileSize: Long? = null,
    val checksum: String? = null,
    @param:Json(name = "class") val className: String,
    val term: String,
    val subject: String,
    val authority: String? = null,
    @param:Json(name = "source_type") val sourceType: String? = null,
    val version: String? = null,
    @param:Json(name = "fragments_count") val fragmentsCount: Int,
    @param:Json(name = "updated_at") val updatedAt: String? = null,
    val fragments: List<RepositoryFragmentDto> = emptyList(),
)
data class RepositoryFragmentDto(
    val id: Long,
    val sequence: Int,
    val theme: String? = null,
    val topic: String,
    val subtopic: String? = null,
    val content: String,
    @param:Json(name = "learning_expectation") val learningExpectation: String? = null,
    @param:Json(name = "source_locator") val sourceLocator: String? = null,
)
data class RepositoryResourcesResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val filters: RepositoryFiltersDto,
    val resources: List<RepositoryResourceDto> = emptyList(),
    val meta: PaginationDto,
)
data class RepositoryResourceResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val resource: RepositoryResourceDto,
)

data class LessonAssignmentDto(
    @param:Json(name = "class_arm_id") val classArmId: Long,
    @param:Json(name = "class_level_id") val classLevelId: Long,
    @param:Json(name = "class_name") val className: String,
    val subjects: List<ScoreSubjectDto> = emptyList(),
)
data class LessonTermDto(
    val id: Long,
    val name: String,
    val session: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)
data class KeyLabelDto(val key: String, val label: String)
data class LessonOptionsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val assignments: List<LessonAssignmentDto> = emptyList(),
    val terms: List<LessonTermDto> = emptyList(),
    @param:Json(name = "curriculum_types") val curriculumTypes: List<KeyLabelDto> = emptyList(),
    @param:Json(name = "delivery_types") val deliveryTypes: List<KeyLabelDto> = emptyList(),
)
data class LessonPlanSectionDto(val key: String, val label: String, val content: String? = null)
data class LessonNoteDto(
    val revision: Int,
    val status: String,
    val depth: String,
    val html: String? = null,
    val content: Map<String, Any?>? = null,
    @param:Json(name = "teacher_edited") val teacherEdited: Boolean = false,
)
data class LessonPlanDto(
    val id: Long,
    val version: String,
    val subject: ScoreSubjectDto? = null,
    @param:Json(name = "class_level") val classLevel: ScoreSubjectDto? = null,
    @param:Json(name = "class_arm") val classArm: ScoreSubjectDto? = null,
    val term: ScoreSubjectDto? = null,
    @param:Json(name = "curriculum_type") val curriculumType: String,
    @param:Json(name = "curriculum_level_id") val curriculumLevelId: Long? = null,
    @param:Json(name = "delivery_type") val deliveryType: String,
    val topic: String,
    val subtopic: String? = null,
    @param:Json(name = "week_number") val weekNumber: Int? = null,
    @param:Json(name = "lesson_number") val lessonNumber: String? = null,
    @param:Json(name = "lesson_time") val lessonTime: String? = null,
    @param:Json(name = "average_age") val averageAge: String? = null,
    val sex: String? = null,
    @param:Json(name = "plan_date") val planDate: String? = null,
    @param:Json(name = "duration_minutes") val durationMinutes: Int,
    val status: String,
    @param:Json(name = "ai_generated") val aiGenerated: Boolean = false,
    @param:Json(name = "has_note") val hasNote: Boolean = false,
    @param:Json(name = "published_at") val publishedAt: String? = null,
    @param:Json(name = "updated_at") val updatedAt: String? = null,
    val sections: List<LessonPlanSectionDto> = emptyList(),
    val note: LessonNoteDto? = null,
)
data class LessonPlansResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    @param:Json(name = "lesson_plans") val lessonPlans: List<LessonPlanDto> = emptyList(),
    val meta: PaginationDto,
)
data class LessonPlanResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String? = null,
    @param:Json(name = "lesson_plan") val lessonPlan: LessonPlanDto,
    val revision: Int? = null,
)
data class LessonPlanMutationRequestDto(
    @param:Json(name = "request_id") val requestId: String,
    val version: String? = null,
    @param:Json(name = "subject_id") val subjectId: Long? = null,
    @param:Json(name = "class_level_id") val classLevelId: Long? = null,
    @param:Json(name = "class_arm_id") val classArmId: Long? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
    @param:Json(name = "curriculum_type") val curriculumType: String? = null,
    @param:Json(name = "curriculum_level_id") val curriculumLevelId: Long? = null,
    @param:Json(name = "delivery_type") val deliveryType: String? = null,
    val topic: String? = null,
    val subtopic: String? = null,
    @param:Json(name = "week_number") val weekNumber: Int? = null,
    @param:Json(name = "lesson_number") val lessonNumber: String? = null,
    @param:Json(name = "lesson_time") val lessonTime: String? = null,
    @param:Json(name = "average_age") val averageAge: String? = null,
    val sex: String? = null,
    @param:Json(name = "plan_date") val planDate: String? = null,
    @param:Json(name = "duration_minutes") val durationMinutes: Int? = null,
    val status: String? = null,
    @param:Json(name = "previous_knowledge") val previousKnowledge: String? = null,
    @param:Json(name = "behavioural_objectives") val behaviouralObjectives: String? = null,
    @param:Json(name = "instructional_materials") val instructionalMaterials: String? = null,
    @param:Json(name = "reference_materials") val referenceMaterials: String? = null,
    @param:Json(name = "set_induction") val setInduction: String? = null,
    val presentation: String? = null,
    val evaluation: String? = null,
    val assignment: String? = null,
    @param:Json(name = "learning_objectives") val learningObjectives: String? = null,
    @param:Json(name = "success_criteria") val successCriteria: String? = null,
    @param:Json(name = "starter_activity") val starterActivity: String? = null,
    @param:Json(name = "class_activity") val classActivity: String? = null,
    val differentiation: String? = null,
    val plenary: String? = null,
    @param:Json(name = "assessment_for_learning") val assessmentForLearning: String? = null,
)
data class VersionedRequestDto(
    @param:Json(name = "request_id") val requestId: String,
    val version: String,
    val depth: String? = null,
)
data class LessonNoteMutationRequestDto(
    @param:Json(name = "request_id") val requestId: String,
    val version: String,
    @param:Json(name = "note_text") val noteText: String,
    @param:Json(name = "note_status") val noteStatus: String,
)

fun RepositoryHierarchyResponseDto.toDomain(fromCache: Boolean = false) = RepositoryHierarchy(
    RepositoryMetrics(metrics.resources, metrics.classes, metrics.subjects, metrics.sections),
    classes.map { group -> RepositoryClassGroup(group.name, group.resourceCount, group.terms.map { term ->
        RepositoryTermGroup(term.name, term.resourceCount, term.subjects.map { RepositorySubjectGroup(it.name, it.resourceCount) })
    }) }, generatedAt, fromCache,
)
fun RepositoryResourceDto.toDomain() = RepositoryResource(
    id, title, filename, mimeType, fileSize, checksum, className, term, subject, authority, sourceType, version, fragmentsCount, updatedAt,
)
fun RepositoryResourcesResponseDto.toDomain(fromCache: Boolean = false) = RepositoryCatalogue(
    RepositoryFilters(filters.classes, filters.terms, filters.subjects),
    resources.map(RepositoryResourceDto::toDomain),
    generatedAt,
    fromCache,
    meta.currentPage,
    meta.lastPage,
    meta.total,
)
fun RepositoryResourceResponseDto.toDomain(fromCache: Boolean = false) = RepositoryResourceDetail(
    resource.toDomain(), resource.fragments.map {
        RepositoryFragment(it.id, it.sequence, it.theme, it.topic, it.subtopic, it.content, it.learningExpectation, it.sourceLocator)
    }, generatedAt, fromCache,
)
fun LessonOptionsResponseDto.toDomain(fromCache: Boolean = false) = LessonPlannerOptions(
    assignments.map { LessonAssignmentOption(it.classArmId, it.classLevelId, it.className, it.subjects.map { subject -> LessonSubjectOption(subject.id, subject.name) }) },
    terms.map { LessonTermOption(it.id, it.name, it.session, it.isCurrent) },
    curriculumTypes.map { KeyLabelOption(it.key, it.label) }, deliveryTypes.map { KeyLabelOption(it.key, it.label) }, generatedAt, fromCache,
)
fun LessonPlanDto.toDomain() = LessonPlan(
    id, version, subject?.let { LessonSubjectOption(it.id, it.name) }, classLevel?.let { LessonSubjectOption(it.id, it.name) },
    classArm?.let { LessonSubjectOption(it.id, it.name) }, term?.let { LessonSubjectOption(it.id, it.name) }, curriculumType,
    curriculumLevelId, deliveryType, topic, subtopic, weekNumber, lessonNumber, lessonTime, averageAge, sex, planDate,
    durationMinutes, status, aiGenerated, hasNote, publishedAt, updatedAt,
    sections.map { LessonPlanSection(it.key, it.label, it.content) }, note?.let {
        LessonNote(it.revision, it.status, it.depth, it.html, htmlToText(it.html), it.teacherEdited)
    },
)

private fun htmlToText(value: String?): String = value.orEmpty()
    .replace(Regex("<br\\s*/?>", RegexOption.IGNORE_CASE), "\n")
    .replace(Regex("</(p|h[1-6]|li)>", RegexOption.IGNORE_CASE), "\n")
    .replace(Regex("<[^>]+>"), "")
    .replace("&nbsp;", " ").replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">")
    .trim()
