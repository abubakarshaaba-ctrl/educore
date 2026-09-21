package online.educoreng.educore.notification

import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.os.Build

/**
 * Single source of truth for the EduCore Android FCM contract.
 *
 * The server sends high-priority data-only messages. EduCoreMessagingService
 * owns rendering, channel selection, deep links and app-update version gating.
 */
object PushNotificationContract {
    const val GENERAL_CHANNEL_ID = "educore_notifications"
    const val APP_UPDATES_TOPIC = "educore_app_updates"

    const val APP_UPDATE_TYPE = "app_update"

    const val KEY_TYPE = "type"
    const val KEY_TITLE = "title"
    const val KEY_BODY = "body"
    const val KEY_VERSION_CODE = "version_code"
    const val KEY_VERSION_NAME = "version_name"
    const val KEY_DOWNLOAD_URL = "download_url"
}

object EduCoreNotificationChannels {
    fun ensureCreated(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.createNotificationChannels(
            listOf(
                NotificationChannel(
                    PushNotificationContract.GENERAL_CHANNEL_ID,
                    "EduCore notifications",
                    NotificationManager.IMPORTANCE_HIGH,
                ).apply {
                    description = "School notices, messages and important EduCore alerts"
                },
                NotificationChannel(
                    PushNotificationContract.APP_UPDATES_TOPIC,
                    "EduCore app updates",
                    NotificationManager.IMPORTANCE_HIGH,
                ).apply {
                    description = "Notifications when a newer EduCore Android version is available"
                },
            ),
        )
    }
}
