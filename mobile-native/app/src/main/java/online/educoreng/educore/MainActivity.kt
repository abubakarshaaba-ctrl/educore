package online.educoreng.educore

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import dagger.hilt.android.AndroidEntryPoint
import online.educoreng.educore.presentation.EduCoreFoundationApp
import online.educoreng.educore.notification.NotificationDeepLinkParser
import online.educoreng.educore.notification.NotificationDeepLinkStore

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        NotificationDeepLinkStore.publish(NotificationDeepLinkParser.parse(intent))
        enableEdgeToEdge()
        setContent { EduCoreFoundationApp() }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        NotificationDeepLinkStore.publish(NotificationDeepLinkParser.parse(intent))
    }
}
