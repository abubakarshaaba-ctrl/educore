package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.model.LessonPlan
import online.educoreng.educore.core.model.LessonPlanDraft
import online.educoreng.educore.core.model.LessonPlannerOptions
import online.educoreng.educore.core.model.RepositoryCatalogue
import online.educoreng.educore.core.model.RepositoryHierarchy
import online.educoreng.educore.core.model.RepositoryResource
import online.educoreng.educore.core.model.RepositoryResourceDetail

interface AcademicContentRepository {
    suspend fun hierarchy(): AppResult<RepositoryHierarchy>
    suspend fun resources(className: String?, term: String?, subject: String?, query: String?, page: Int = 1): AppResult<RepositoryCatalogue>
    suspend fun resource(id: Long): AppResult<RepositoryResourceDetail>
    suspend fun downloadResource(resource: RepositoryResource): AppResult<DownloadedDocument>
    suspend fun lessonOptions(): AppResult<LessonPlannerOptions>
    suspend fun lessonPlans(): AppResult<List<LessonPlan>>
    suspend fun lessonPlan(id: Long): AppResult<LessonPlan>
    suspend fun localDraft(key: String): LessonPlanDraft?
    suspend fun saveLocalDraft(key: String, draft: LessonPlanDraft)
    suspend fun discardLocalDraft(key: String)
    suspend fun saveLesson(draft: LessonPlanDraft): AppResult<LessonPlan>
    suspend fun generateLesson(plan: LessonPlan): AppResult<LessonPlan>
    suspend fun generateNote(plan: LessonPlan, depth: String): AppResult<LessonPlan>
    suspend fun updateNote(plan: LessonPlan, text: String, status: String): AppResult<LessonPlan>
    suspend fun publishLesson(plan: LessonPlan): AppResult<LessonPlan>
    suspend fun downloadLessonPdf(plan: LessonPlan, note: Boolean): AppResult<DownloadedDocument>
}
