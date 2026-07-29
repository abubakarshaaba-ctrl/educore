package online.educoreng.educore

import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import android.os.Bundle
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                "educore_updates",
                "EduCore updates",
                NotificationManager.IMPORTANCE_HIGH,
            ).apply {
                description = "Announcements, messages, exam duties, and school updates"
                enableVibration(true)
            }

            getSystemService(NotificationManager::class.java)
                .createNotificationChannel(channel)
        }
    }
}
