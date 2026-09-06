package online.educoreng.educore.core.data.sync

import androidx.work.Constraints
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequest
import androidx.work.WorkManager

class BackgroundSyncScheduler(private val workManager: WorkManager) {
    fun enqueueUnique(name: String, request: OneTimeWorkRequest) {
        require(name.isNotBlank())
        workManager.enqueueUniqueWork(name, ExistingWorkPolicy.KEEP, request)
    }

    companion object {
        fun connectedNetworkConstraints(): Constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
    }
}
