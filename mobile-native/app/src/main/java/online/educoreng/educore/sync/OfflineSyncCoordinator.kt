package online.educoreng.educore.sync

import androidx.work.BackoffPolicy
import androidx.work.OneTimeWorkRequestBuilder
import java.util.concurrent.TimeUnit
import javax.inject.Inject
import online.educoreng.educore.core.data.sync.BackgroundSyncScheduler

class OfflineSyncCoordinator @Inject constructor(private val scheduler: BackgroundSyncScheduler) {
    fun schedule() {
        val request = OneTimeWorkRequestBuilder<OfflineSyncWorker>()
            .setConstraints(BackgroundSyncScheduler.connectedNetworkConstraints())
            .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 15, TimeUnit.SECONDS)
            .build()
        scheduler.enqueueUnique(WORK_NAME, request)
    }

    private companion object { const val WORK_NAME = "educore-explicit-write-sync" }
}
