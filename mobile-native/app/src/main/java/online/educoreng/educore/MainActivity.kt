package online.educoreng.educore

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.core.view.WindowCompat
import dagger.hilt.android.AndroidEntryPoint
import online.educoreng.educore.notification.NotificationDeepLinkParser
import online.educoreng.educore.notification.NotificationDeepLinkStore
import online.educoreng.educore.presentation.EduCoreFoundationApp

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        NotificationDeepLinkStore.publish(NotificationDeepLinkParser.parse(intent))
        enableEdgeToEdge()
        // EduCore uses a transparent edge-to-edge status bar over a dark navy header.
        // Force light system icons so time, signal and notification indicators remain legible.
        WindowCompat.getInsetsController(window, window.decorView).isAppearanceLightStatusBars = false
        setContent { EduCoreFoundationApp() }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        NotificationDeepLinkStore.publish(NotificationDeepLinkParser.parse(intent))
    }
}
