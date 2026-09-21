package online.educoreng.educore.notification

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.graphics.BitmapFactory
import android.net.Uri
import androidx.core.app.NotificationCompat
import androidx.core.content.ContextCompat
import com.google.firebase.messaging.FirebaseMessaging
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.launch
import online.educoreng.educore.BuildConfig
import online.educoreng.educore.MainActivity
import online.educoreng.educore.R
import online.educoreng.educore.core.data.repository.CommunicationRepository
import online.educoreng.educore.core.data.repository.SessionRepository

@AndroidEntryPoint
class EduCoreMessagingService : FirebaseMessagingService() {
    @Inject lateinit var communicationRepository: CommunicationRepository
    @Inject lateinit var sessionRepository: SessionRepository

    private val serviceScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    override fun onNewToken(token: String) {
        FirebaseMessaging.getInstance()
            .subscribeToTopic(PushNotificationContract.APP_UPDATES_TOPIC)

        // User-specific tokens need an authenticated API session. If Firebase
        // rotates while signed out, MainViewModel registers the current token
        // again as soon as the next authenticated session becomes ready.
        if (sessionRepository.hasStoredToken()) {
            serviceScope.launch {
                communicationRepository.registerPushToken(token)
            }
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        val data = message.data
        if (data.isEmpty()) return

        val isAppUpdate =
            data[PushNotificationContract.KEY_TYPE] == PushNotificationContract.APP_UPDATE_TYPE

        // Tenant/user notifications must never surface after the local session
        // has been cleared. App-update notifications are installation-scoped.
        if (!isAppUpdate && !sessionRepository.hasStoredToken()) return

        if (isAppUpdate) {
            val announcedVersionCode =
                data[PushNotificationContract.KEY_VERSION_CODE]?.toIntOrNull()
            if (announcedVersionCode != null && announcedVersionCode <= BuildConfig.VERSION_CODE) {
                return
            }
        }

        val target = if (isAppUpdate) null else NotificationDeepLinkParser.parse(data)
        if (target != null) {
            NotificationDeepLinkStore.publish(target)
        }

        val versionName = data[PushNotificationContract.KEY_VERSION_NAME].orEmpty()
        val updateBody = versionName
            .takeIf(String::isNotBlank)
            ?.let { "EduCore $it is ready to install." }
            ?: "A new EduCore version is ready to install."

        showNotification(
            title = data[PushNotificationContract.KEY_TITLE]
                ?: if (isAppUpdate) "EduCore update available" else getString(R.string.app_name),
            body = data[PushNotificationContract.KEY_BODY]
                ?: if (isAppUpdate) updateBody else "You have a new EduCore notification.",
            target = target,
            downloadUrl = data[PushNotificationContract.KEY_DOWNLOAD_URL]
                .takeIf { isAppUpdate && !it.isNullOrBlank() },
            isAppUpdate = isAppUpdate,
            messageId = message.messageId,
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
        messageId: String? = null,
    ) {
        EduCoreNotificationChannels.ensureCreated(this)

        val channelId = if (isAppUpdate) {
            PushNotificationContract.APP_UPDATES_TOPIC
        } else {
            PushNotificationContract.GENERAL_CHANNEL_ID
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

        val requestCode = messageId
            ?.hashCode()
            ?.and(0x7fffffff)
            ?: (System.currentTimeMillis() and 0x7fffffff).toInt()

        val pendingIntent = PendingIntent.getActivity(
            this,
            requestCode,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val largeIcon = runCatching {
            BitmapFactory.decodeResource(resources, R.drawable.educore_app_icon)
        }.getOrNull()

        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.notify(
            requestCode,
            NotificationCompat.Builder(this, channelId)
                .setSmallIcon(R.drawable.ic_educore_notification)
                .setLargeIcon(largeIcon)
                .setColor(ContextCompat.getColor(this, R.color.educore_notification_accent))
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(NotificationCompat.BigTextStyle().bigText(body))
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setAutoCancel(true)
                .setContentIntent(pendingIntent)
                .build(),
        )
    }
}
