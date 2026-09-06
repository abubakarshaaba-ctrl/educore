package online.educoreng.educore.core.data.repository

import android.content.Context
import com.squareup.moshi.Moshi
import java.util.UUID
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import okhttp3.ResponseBody
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.CachedAcademicContentEntity
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.local.LessonPlanDraftEntity
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.LessonPlan
import online.educoreng.educore.core.model.LessonPlanDraft
import online.educoreng.educore.core.model.LessonPlannerOptions
import online.educoreng.educore.core.model.RepositoryCatalogue
import online.educoreng.educore.core.model.RepositoryHierarchy
import online.educoreng.educore.core.model.RepositoryResource
import online.educoreng.educore.core.model.RepositoryResourceDetail
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.LessonOptionsResponseDto
import online.educoreng.educore.core.network.dto.LessonPlanDto
import online.educoreng.educore.core.network.dto.LessonPlanMutationRequestDto
import online.educoreng.educore.core.network.dto.LessonPlansResponseDto
import online.educoreng.educore.core.network.dto.LessonNoteMutationRequestDto
import online.educoreng.educore.core.network.dto.RepositoryHierarchyResponseDto
import online.educoreng.educore.core.network.dto.RepositoryResourceResponseDto
import online.educoreng.educore.core.network.dto.RepositoryResourcesResponseDto
import online.educoreng.educore.core.network.dto.VersionedRequestDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.network.safeApiCall

class DefaultAcademicContentRepository(
    private val context: Context,
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : AcademicContentRepository {
    private val hierarchyAdapter by lazy { moshi.adapter(RepositoryHierarchyResponseDto::class.java) }
    private val resourcesAdapter by lazy { moshi.adapter(RepositoryResourcesResponseDto::class.java) }
    private val resourceAdapter by lazy { moshi.adapter(RepositoryResourceResponseDto::class.java) }
    private val optionsAdapter by lazy { moshi.adapter(LessonOptionsResponseDto::class.java) }
    private val plansAdapter by lazy { moshi.adapter(LessonPlansResponseDto::class.java) }
    private val draftAdapter by lazy { moshi.adapter(LessonPlanDraft::class.java) }
    private val pendingRequestIds = mutableMapOf<String, String>()

    override suspend fun hierarchy(): AppResult<RepositoryHierarchy> = cached(
        key = "repository:hierarchy", adapter = hierarchyAdapter,
        remote = { api.repositoryHierarchy() }, domain = { dto, cached -> dto.toDomain(cached) },
    )

    override suspend fun resources(className: String?, term: String?, subject: String?, query: String?, page: Int): AppResult<RepositoryCatalogue> {
        val safePage = page.coerceAtLeast(1)
        val key = listOf("repository", className.orEmpty(), term.orEmpty(), subject.orEmpty(), query.orEmpty(), safePage.toString()).joinToString(":")
        return cached(
            key,
            resourcesAdapter,
            remote = { api.repositoryResources(className, term, subject, query, perPage = REPOSITORY_PAGE_SIZE, page = safePage) },
            domain = { dto, cached -> dto.toDomain(cached) },
        )
    }

    override suspend fun resource(id: Long): AppResult<RepositoryResourceDetail> = cached(
        key = "repository:resource:$id", adapter = resourceAdapter,
        remote = { api.repositoryResource(id) }, domain = { dto, cached -> dto.toDomain(cached) },
    )

    override suspend fun downloadResource(resource: RepositoryResource): AppResult<DownloadedDocument> = download(
        filename = resource.filename ?: "${slug(resource.title)}.bin",
        mimeType = resource.mimeType ?: "application/octet-stream",
    ) { api.downloadRepositoryResource(resource.id) }

    override suspend fun lessonOptions(): AppResult<LessonPlannerOptions> = cached(
        key = "lesson:options", adapter = optionsAdapter,
        remote = { api.lessonOptions() }, domain = { dto, cached -> dto.toDomain(cached) },
    )

    override suspend fun lessonPlans(): AppResult<List<LessonPlan>> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        val key = "lesson:list"
        val result = safeApiCall(moshi) {
            var page = 1
            val first = api.lessonPlans(page = page)
            val all = first.lessonPlans.toMutableList()
            while (page < first.meta.lastPage) {
                page++
                all += api.lessonPlans(page = page).lessonPlans
            }
            first.copy(lessonPlans = all, meta = first.meta.copy(currentPage = 1, lastPage = 1, total = all.size))
        }
        when (result) {
            is AppResult.Success -> {
                cache(scope, key, plansAdapter.toJson(result.value))
                AppResult.Success(result.value.lessonPlans.map(LessonPlanDto::toDomain))
            }
            is AppResult.Failure -> {
                val cached = database.academicContentDao().cache(scope.first, scope.second, key)
                    ?.let { runCatching { plansAdapter.fromJson(it.payloadJson) }.getOrNull() }
                cached?.let { AppResult.Success(it.lessonPlans.map(LessonPlanDto::toDomain)) } ?: result
            }
        }
    }

    override suspend fun lessonPlan(id: Long): AppResult<LessonPlan> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.lessonPlan(id) }) {
            is AppResult.Success -> AppResult.Success(result.value.lessonPlan.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun localDraft(key: String): LessonPlanDraft? = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext null
        database.academicContentDao().draft(scope.first, scope.second, key)
            ?.let { runCatching { draftAdapter.fromJson(it.payloadJson) }.getOrNull() }
    }

    override suspend fun saveLocalDraft(key: String, draft: LessonPlanDraft) = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext
        database.academicContentDao().replaceDraft(LessonPlanDraftEntity(scope.first, scope.second, key, draftAdapter.toJson(draft), nowEpochMs()))
    }

    override suspend fun discardLocalDraft(key: String) = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext
        database.academicContentDao().deleteDraft(scope.first, scope.second, key)
    }

    override suspend fun saveLesson(draft: LessonPlanDraft): AppResult<LessonPlan> = withContext(Dispatchers.IO) {
        val request = draft.toRequest()
        val remoteId = draft.remoteId
        when (val result = safeApiCall(moshi) {
            if (remoteId == null) api.createLessonPlan(request) else api.updateLessonPlan(remoteId, request)
        }) {
            is AppResult.Success -> {
                discardLocalDraft(draft.remoteId?.let { "plan:$it" } ?: "new")
                AppResult.Success(result.value.lessonPlan.toDomain())
            }
            is AppResult.Failure -> result
        }
    }

    override suspend fun generateLesson(plan: LessonPlan) = idempotentMutate("generate:${plan.id}:${plan.version}") { requestId ->
        api.generateLessonPlan(plan.id, VersionedRequestDto(requestId, plan.version)).lessonPlan
    }
    override suspend fun generateNote(plan: LessonPlan, depth: String) = idempotentMutate("note-generate:${plan.id}:${plan.version}:$depth") { requestId ->
        api.generateLessonNote(plan.id, VersionedRequestDto(requestId, plan.version, depth)).lessonPlan
    }
    override suspend fun updateNote(plan: LessonPlan, text: String, status: String) = idempotentMutate("note-update:${plan.id}:${plan.version}:${text.hashCode()}:$status") { requestId ->
        api.updateLessonNote(plan.id, LessonNoteMutationRequestDto(requestId, plan.version, text, status)).lessonPlan
    }
    override suspend fun publishLesson(plan: LessonPlan) = idempotentMutate("publish:${plan.id}:${plan.version}") { requestId ->
        api.publishLessonPlan(plan.id, VersionedRequestDto(requestId, plan.version)).lessonPlan
    }

    override suspend fun downloadLessonPdf(plan: LessonPlan, note: Boolean): AppResult<DownloadedDocument> = download(
        filename = "${slug(plan.topic)}-${if (note) "student-note" else "lesson-plan"}.pdf",
        mimeType = "application/pdf",
    ) { if (note) api.downloadLessonNotePdf(plan.id) else api.downloadLessonPlanPdf(plan.id) }

    private suspend fun mutate(block: suspend () -> LessonPlanDto): AppResult<LessonPlan> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi, block)) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    private suspend fun idempotentMutate(key: String, block: suspend (String) -> LessonPlanDto): AppResult<LessonPlan> {
        val requestId = synchronized(pendingRequestIds) { pendingRequestIds.getOrPut(key) { UUID.randomUUID().toString() } }
        val result = mutate { block(requestId) }
        if (result is AppResult.Success) synchronized(pendingRequestIds) { pendingRequestIds.remove(key) }
        return result
    }

    private suspend fun <D : Any, T> cached(
        key: String,
        adapter: com.squareup.moshi.JsonAdapter<D>,
        remote: suspend () -> D,
        domain: (D, Boolean) -> T,
    ): AppResult<T> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        when (val result = safeApiCall(moshi, remote)) {
            is AppResult.Success -> {
                cache(scope, key, adapter.toJson(result.value))
                AppResult.Success(domain(result.value, false))
            }
            is AppResult.Failure -> {
                val cached = database.academicContentDao().cache(scope.first, scope.second, key)
                    ?.let { runCatching { adapter.fromJson(it.payloadJson) }.getOrNull() }
                cached?.let { AppResult.Success(domain(it, true)) } ?: result
            }
        }
    }

    private suspend fun download(filename: String, mimeType: String, remote: suspend () -> ResponseBody): AppResult<DownloadedDocument> =
        withContext(Dispatchers.IO) {
            when (val result = safeApiCall(moshi, remote)) {
                is AppResult.Success -> runCatching {
                    saveDownloadedDocument(context, result.value, filename, mimeType)
                }.fold({ AppResult.Success(it) }, { AppResult.Failure(AppError.Unexpected("The document could not be saved.", it)) })
                is AppResult.Failure -> result
            }
        }

    private suspend fun scope(): Pair<String, Long>? {
        val tenantKey = tenantContextStore.activeTenantKey.first() ?: return null
        val userId = database.sessionDao().get(tenantKey)?.userId ?: return null
        return tenantKey to userId
    }

    private suspend fun cache(scope: Pair<String, Long>, key: String, json: String) {
        database.academicContentDao().replaceCache(CachedAcademicContentEntity(scope.first, scope.second, key, json, nowEpochMs()))
    }

    private fun LessonPlanDraft.toRequest() = LessonPlanMutationRequestDto(
        requestId = requestId, version = version, subjectId = subjectId, classLevelId = classLevelId,
        classArmId = classArmId, termId = termId, curriculumType = curriculumType, curriculumLevelId = curriculumLevelId,
        deliveryType = deliveryType, topic = topic, subtopic = subtopic.ifBlank { null }, weekNumber = weekNumber.toIntOrNull(),
        lessonNumber = lessonNumber.ifBlank { null }, lessonTime = lessonTime.ifBlank { null }, averageAge = averageAge.ifBlank { null },
        sex = sex, planDate = planDate.ifBlank { null }, durationMinutes = durationMinutes.toIntOrNull(), status = status,
        previousKnowledge = sections["previous_knowledge"], behaviouralObjectives = sections["behavioural_objectives"],
        instructionalMaterials = sections["instructional_materials"], referenceMaterials = sections["reference_materials"],
        setInduction = sections["set_induction"], presentation = sections["presentation"], evaluation = sections["evaluation"],
        assignment = sections["assignment"], learningObjectives = sections["learning_objectives"], successCriteria = sections["success_criteria"],
        starterActivity = sections["starter_activity"], classActivity = sections["class_activity"], differentiation = sections["differentiation"],
        plenary = sections["plenary"], assessmentForLearning = sections["assessment_for_learning"],
    )

    private fun slug(value: String) = value.lowercase().replace(Regex("[^a-z0-9]+"), "-").trim('-').ifBlank { "educore-document" }

    private companion object {
        const val REPOSITORY_PAGE_SIZE = 50
    }
}
