package online.educoreng.educore.notification

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
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
        val target = NotificationDeepLinkParser.parse(message.data)
        if (target != null) NotificationDeepLinkStore.publish(target)
        showNotification(
            title = message.notification?.title ?: message.data["title"] ?: getString(R.string.app_name),
            body = message.notification?.body ?: message.data["body"] ?: "You have a new EduCore update.",
            target = target,
        )
    }

    override fun onDestroy() {
        serviceScope.cancel()
        super.onDestroy()
    }

    private fun showNotification(title: String, body: String, target: online.educoreng.educore.core.model.DeepLinkTarget?) {
        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            manager.createNotificationChannel(
                NotificationChannel(CHANNEL_ID, "EduCore updates", NotificationManager.IMPORTANCE_HIGH).apply {
                    description = "School notices, messages and important academic updates"
                },
            )
        }
        val intent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
            putExtra("destination_type", target?.type)
            putExtra("destination_id", target?.id)
        }
        val requestCode = (System.currentTimeMillis() and 0x7fffffff).toInt()
        val pendingIntent = PendingIntent.getActivity(
            this,
            requestCode,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
        manager.notify(
            requestCode,
            NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_educore_mark)
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(NotificationCompat.BigTextStyle().bigText(body))
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setAutoCancel(true)
                .setContentIntent(pendingIntent)
                .build(),
        )
    }

    private companion object { const val CHANNEL_ID = "educore_updates" }
}
