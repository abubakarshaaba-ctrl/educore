package online.educoreng.educore

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.os.Build
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

        createNotificationChannels()

        // FCM is the fastest update-delivery path. WorkManager remains an
        // independent fallback if a topic push is delayed or missed.
        FirebaseMessaging.getInstance()
            .subscribeToTopic(APP_UPDATES_TOPIC)

        scheduleAppUpdateChecks()
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.createNotificationChannels(
            listOf(
                NotificationChannel(
                    GENERAL_CHANNEL_ID,
                    "EduCore notifications",
                    NotificationManager.IMPORTANCE_HIGH,
                ).apply {
                    description = "School notices, messages and important EduCore alerts"
                },
                NotificationChannel(
                    APP_UPDATES_TOPIC,
                    "EduCore app updates",
                    NotificationManager.IMPORTANCE_HIGH,
                ).apply {
                    description = "Notifications when a newer EduCore Android version is available"
                },
            ),
        )
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

        val periodicCheck = PeriodicWorkRequestBuilder<AppUpdateWorker>(1, TimeUnit.HOURS)
            .setConstraints(connectedNetwork)
            .build()

        WorkManager.getInstance(this).enqueueUniquePeriodicWork(
            APP_UPDATE_PERIODIC_WORK,
            ExistingPeriodicWorkPolicy.UPDATE,
            periodicCheck,
        )
    }

    private companion object {
        const val GENERAL_CHANNEL_ID = "educore_notifications"
        const val APP_UPDATES_TOPIC = "educore_app_updates"
        const val APP_UPDATE_IMMEDIATE_WORK = "educore_app_update_immediate"
        const val APP_UPDATE_PERIODIC_WORK = "educore_app_update_periodic"
    }
}
