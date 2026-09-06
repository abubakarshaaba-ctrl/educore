package online.educoreng.educore.sync

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import dagger.hilt.EntryPoint
import dagger.hilt.InstallIn
import dagger.hilt.android.EntryPointAccessors
import dagger.hilt.components.SingletonComponent
import online.educoreng.educore.core.data.repository.ClassWorkspaceRepository
import online.educoreng.educore.core.data.repository.ScoreWorkspaceRepository

class OfflineSyncWorker(context: Context, params: WorkerParameters) : CoroutineWorker(context, params) {
    override suspend fun doWork(): Result {
        val dependencies = EntryPointAccessors.fromApplication(applicationContext, Dependencies::class.java)
        val shouldRetry = dependencies.classes().syncPendingAttendance() or dependencies.scores().syncPendingScores()
        return if (shouldRetry) Result.retry() else Result.success()
    }

    @EntryPoint
    @InstallIn(SingletonComponent::class)
    interface Dependencies {
        fun classes(): ClassWorkspaceRepository
        fun scores(): ScoreWorkspaceRepository
    }
}
