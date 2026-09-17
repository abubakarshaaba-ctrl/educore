package online.educoreng.educore.notification

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Build
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.launch
import online.educoreng.educore.MainActivity
import online.educoreng.educore.R
import online.educoreng.educore.core.data.repository.CommunicationRepository

@AndroidEntryPoint
class EduCoreMessagingService : FirebaseMessagingService() {
    @Inject lateinit var communicationRepository: CommunicationRepository

    private val serviceScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    override fun onNewToken(token: String) {
        serviceScope.launch { communicationRepository.registerPushToken(token) }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        val isAppUpdate = message.data["type"] == APP_UPDATE_TYPE
        val target = if (isAppUpdate) null else NotificationDeepLinkParser.parse(message.data)
        if (target != null) NotificationDeepLinkStore.publish(target)

        val versionName = message.data["version_name"].orEmpty()
        val updateBody = versionName
            .takeIf(String::isNotBlank)
            ?.let { "EduCore $it is ready to install." }
            ?: "A new EduCore version is ready to install."

        showNotification(
            title = message.notification?.title
                ?: message.data["title"]
                ?: if (isAppUpdate) "EduCore update available" else getString(R.string.app_name),
            body = message.notification?.body
                ?: message.data["body"]
                ?: if (isAppUpdate) updateBody else "You have a new EduCore notification.",
            target = target,
            downloadUrl = message.data["download_url"].takeIf { isAppUpdate && !it.isNullOrBlank() },
            isAppUpdate = isAppUpdate,
        )
    }

    override fun onDestroy() {
        serviceScope.cancel()
        super.onDestroy()
    }

    private fun showNotification(
        title: String,
        body: String,
        target: online.educoreng.educore.core.model.DeepLinkTarget?,
        downloadUrl: String? = null,
        isAppUpdate: Boolean = false,
    ) {
        val channelId = if (isAppUpdate) APP_UPDATE_CHANNEL_ID else GENERAL_CHANNEL_ID
        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            manager.createNotificationChannel(
                NotificationChannel(
                    channelId,
                    if (isAppUpdate) "EduCore app updates" else "EduCore notifications",
                    NotificationManager.IMPORTANCE_HIGH,
                ).apply {
                    description = if (isAppUpdate) {
                        "Notifications when a newer EduCore Android version is available"
                    } else {
                        "School notices, messages and important EduCore alerts"
                    }
                },
            )
        }

        val intent = if (!downloadUrl.isNullOrBlank()) {
            Intent(Intent.ACTION_VIEW, Uri.parse(downloadUrl)).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK
            }
        } else {
            Intent(this, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
                putExtra("destination_type", target?.type)
                putExtra("destination_id", target?.id)
            }
        }
        val requestCode = (System.currentTimeMillis() and 0x7fffffff).toInt()
        val pendingIntent = PendingIntent.getActivity(
            this,
            requestCode,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val largeIcon = runCatching {
            BitmapFactory.decodeResource(resources, R.drawable.educore_app_icon)
        }.getOrNull()

        manager.notify(
            requestCode,
            NotificationCompat.Builder(this, channelId)
                .setSmallIcon(R.drawable.ic_educore_notification)
                .setLargeIcon(largeIcon)
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(NotificationCompat.BigTextStyle().bigText(body))
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setAutoCancel(true)
                .setContentIntent(pendingIntent)
                .build(),
        )
    }

    private companion object {
        const val GENERAL_CHANNEL_ID = "educore_notifications"
        const val APP_UPDATE_CHANNEL_ID = "educore_app_updates"
        const val APP_UPDATE_TYPE = "app_update"
    }
}
