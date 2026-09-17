package online.educoreng.educore.update

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import online.educoreng.educore.R

class AppUpdateWorker(
    appContext: Context,
    params: WorkerParameters,
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        val update = AppUpdateChecker.check() ?: return Result.retry()
        if (!update.isUpdateAvailable) return Result.success()

        val prefs = applicationContext.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
        val lastNotifiedCode = prefs.getInt(KEY_LAST_NOTIFIED_VERSION_CODE, -1)
        if (lastNotifiedCode >= update.latestVersionCode) return Result.success()

        if (
            Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(applicationContext, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            // The in-app update dialog remains available on launch. Once the
            // user grants notification permission a later worker run will show
            // the system notification because we intentionally do not persist
            // this version as already notified here.
            return Result.success()
        }

        ensureUpdateChannel()

        val downloadIntent = Intent(Intent.ACTION_VIEW, Uri.parse(update.downloadUrl)).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK
        }
        val pendingIntent = PendingIntent.getActivity(
            applicationContext,
            update.latestVersionCode,
            downloadIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val largeIcon = runCatching {
            BitmapFactory.decodeResource(applicationContext.resources, R.drawable.educore_app_icon)
        }.getOrNull()

        val notification = NotificationCompat.Builder(applicationContext, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_educore_notification)
            .setLargeIcon(largeIcon)
            .setColor(ContextCompat.getColor(applicationContext, R.color.educore_notification_accent))
            .setContentTitle(if (update.isRequired) "EduCore update required" else "EduCore update available")
            .setContentText("Version ${update.latestVersionName} is ready to install.")
            .setStyle(
                NotificationCompat.BigTextStyle().bigText(
                    buildString {
                        append("Version ${update.latestVersionName} is ready to install.")
                        if (update.releaseNotes.isNotBlank()) {
                            append("\n\n")
                            append(update.releaseNotes)
                        }
                    },
                ),
            )
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_STATUS)
            .setOnlyAlertOnce(true)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .build()

        NotificationManagerCompat.from(applicationContext).notify(NOTIFICATION_ID, notification)
        prefs.edit().putInt(KEY_LAST_NOTIFIED_VERSION_CODE, update.latestVersionCode).apply()
        return Result.success()
    }

    private fun ensureUpdateChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val manager = applicationContext.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.createNotificationChannel(
            NotificationChannel(
                CHANNEL_ID,
                "EduCore app updates",
                NotificationManager.IMPORTANCE_HIGH,
            ).apply {
                description = "Notifications when a newer EduCore Android version is available"
            },
        )
    }

    private companion object {
        const val CHANNEL_ID = "educore_app_updates"
        const val NOTIFICATION_ID = 0xEC02
        const val PREFS_NAME = "educore_update_notifications"
        const val KEY_LAST_NOTIFIED_VERSION_CODE = "last_notified_version_code"
    }
}
