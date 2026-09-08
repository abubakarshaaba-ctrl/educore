package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.PublishedResults
import online.educoreng.educore.core.model.ScoreAssignments
import online.educoreng.educore.core.model.ScoreSheet

interface ScoreWorkspaceRepository {
    suspend fun loadAssignments(): AppResult<ScoreAssignments>
    suspend fun loadSheet(classId: Long, subjectId: Long, termId: Long?): AppResult<ScoreSheet>
    suspend fun saveDraft(sheet: ScoreSheet, studentId: Long, assessmentId: Long, value: Double?): AppResult<ScoreSheet>
    suspend fun discardDraft(sheet: ScoreSheet): AppResult<Unit>
    suspend fun submit(sheet: ScoreSheet): AppResult<ScoreSheet>
    suspend fun syncPendingScores(): Boolean
    suspend fun loadPublishedResults(childId: Long? = null): AppResult<PublishedResults>
    suspend fun loadStudentResults(classId: Long, studentId: Long): AppResult<PublishedResults>
}
