package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.GeneratedKnowledgeDocument
import online.educoreng.educore.core.model.KnowledgeBlock
import online.educoreng.educore.core.model.KnowledgeCatalogue
import online.educoreng.educore.core.model.KnowledgeConsolidation
import online.educoreng.educore.core.model.KnowledgeFields
import online.educoreng.educore.core.model.KnowledgeMutationResult
import online.educoreng.educore.core.model.KnowledgeReadiness
import online.educoreng.educore.core.model.KnowledgeSource
import online.educoreng.educore.core.model.KnowledgeTopic

data class KnowledgeReadinessDto(
    val score: Int,
    @param:Json(name = "coverage_score") val coverageScore: Int,
    @param:Json(name = "quality_score") val qualityScore: Int? = null,
    val ready: Boolean,
    val missing: List<String> = emptyList(),
    val critical: List<String> = emptyList(),
)

data class KnowledgeSourceDto(
    val title: String? = null,
    val filename: String? = null,
    @param:Json(name = "resource_type") val resourceType: String? = null,
    val priority: Int? = null,
    @param:Json(name = "is_official") val official: Boolean = false,
    @param:Json(name = "is_primary") val primary: Boolean = false,
)

data class KnowledgeConsolidationDto(
    @param:Json(name = "source_count") val sourceCount: Int = 0,
    @param:Json(name = "topic_count") val topicCount: Int = 0,
    @param:Json(name = "usable_source_count") val usableSourceCount: Int = 0,
)

data class KnowledgeFieldsDto(
    val time: String? = null,
    val duration: Int? = null,
    @param:Json(name = "average_age") val averageAge: Int? = null,
    val sex: String? = null,
    @param:Json(name = "entry_behaviour") val entryBehaviour: String? = null,
    @param:Json(name = "previous_knowledge") val previousKnowledge: String? = null,
    @param:Json(name = "instructional_resources") val instructionalResources: String? = null,
    val introduction: String? = null,
    @param:Json(name = "student_note_summary") val studentNoteSummary: String? = null,
    val reference: String? = null,
)

data class KnowledgeBlockDto(val type: String, val sequence: Int, val title: String? = null, val content: String)

data class KnowledgeTopicDto(
    val id: Long,
    @param:Json(name = "class") val className: String,
    val subject: String,
    val term: String,
    val week: Int? = null,
    val lesson: Int? = null,
    val topic: String,
    @param:Json(name = "sub_topic") val subTopic: String? = null,
    @param:Json(name = "resource_type") val resourceType: String,
    val readiness: KnowledgeReadinessDto,
    val consolidation: KnowledgeConsolidationDto? = null,
    val sources: List<KnowledgeSourceDto> = emptyList(),
    val fields: KnowledgeFieldsDto? = null,
    val blocks: List<KnowledgeBlockDto> = emptyList(),
)

data class KnowledgeCatalogueResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val topics: List<KnowledgeTopicDto> = emptyList(),
    val meta: PaginationDto,
)

data class KnowledgeTopicResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val topic: KnowledgeTopicDto,
)

data class KnowledgeDocumentSectionDto(val heading: String, val content: String)
data class KnowledgeDocumentDto(val title: String, val sections: List<KnowledgeDocumentSectionDto> = emptyList())
data class GeneratedKnowledgeResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    @param:Json(name = "generated_at") val generatedAt: String,
    val type: String,
    val topic: KnowledgeTopicDto,
    val document: KnowledgeDocumentDto,
)

data class KnowledgeMutationResponseDto(
    val message: String,
    @param:Json(name = "lesson_plan_id") val lessonPlanId: Long? = null,
    val revision: Int? = null,
)

fun KnowledgeTopicDto.toDomain() = KnowledgeTopic(
    id, className, subject, term, week, lesson, topic, subTopic, resourceType,
    KnowledgeReadiness(readiness.score, readiness.coverageScore, readiness.qualityScore, readiness.ready, readiness.missing, readiness.critical),
    consolidation?.let { KnowledgeConsolidation(it.sourceCount, it.topicCount, it.usableSourceCount) },
    sources.map { KnowledgeSource(it.title, it.filename, it.resourceType, it.priority, it.official, it.primary) },
    fields?.let { KnowledgeFields(it.time, it.duration, it.averageAge, it.sex, it.entryBehaviour, it.previousKnowledge, it.instructionalResources, it.introduction, it.studentNoteSummary, it.reference) },
    blocks.map { KnowledgeBlock(it.type, it.sequence, it.title, it.content) },
)

fun KnowledgeCatalogueResponseDto.toDomain() = KnowledgeCatalogue(topics.map(KnowledgeTopicDto::toDomain), meta.currentPage, meta.lastPage, meta.total)
fun GeneratedKnowledgeResponseDto.toDomain() = GeneratedKnowledgeDocument(type, topic.toDomain(), document.title, document.sections.map { it.heading to it.content })
fun KnowledgeMutationResponseDto.toDomain() = KnowledgeMutationResult(message, lessonPlanId, revision)
