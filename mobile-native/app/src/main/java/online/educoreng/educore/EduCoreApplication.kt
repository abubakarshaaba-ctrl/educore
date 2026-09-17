package online.educoreng.educore

import android.app.Application
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import com.google.firebase.messaging.FirebaseMessaging
import dagger.hilt.android.HiltAndroidApp
import java.util.concurrent.TimeUnit
import online.educoreng.educore.update.AppUpdateWorker

@HiltAndroidApp
class EduCoreApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Keep FCM as the fastest update-delivery path. The WorkManager polling
        // below is an independent fallback so release notifications still reach
        // devices when a topic push is delayed or missed.
        FirebaseMessaging.getInstance()
            .subscribeToTopic(APP_UPDATES_TOPIC)

        scheduleAppUpdateChecks()
    }

    private fun scheduleAppUpdateChecks() {
        val connectedNetwork = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()

        val immediateCheck = OneTimeWorkRequestBuilder<AppUpdateWorker>()
            .setConstraints(connectedNetwork)
            .build()

        WorkManager.getInstance(this).enqueueUniqueWork(
            APP_UPDATE_IMMEDIATE_WORK,
            ExistingWorkPolicy.REPLACE,
            immediateCheck,
        )

        val periodicCheck = PeriodicWorkRequestBuilder<AppUpdateWorker>(6, TimeUnit.HOURS)
            .setConstraints(connectedNetwork)
            .build()

        WorkManager.getInstance(this).enqueueUniquePeriodicWork(
            APP_UPDATE_PERIODIC_WORK,
            ExistingPeriodicWorkPolicy.UPDATE,
            periodicCheck,
        )
    }

    private companion object {
        const val APP_UPDATES_TOPIC = "educore_app_updates"
        const val APP_UPDATE_IMMEDIATE_WORK = "educore_app_update_immediate"
        const val APP_UPDATE_PERIODIC_WORK = "educore_app_update_periodic"
    }
}
