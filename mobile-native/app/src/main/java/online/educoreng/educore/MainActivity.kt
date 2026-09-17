package online.educoreng.educore

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.content.ContextCompat
import androidx.core.view.WindowCompat
import androidx.work.Constraints
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import dagger.hilt.android.AndroidEntryPoint
import online.educoreng.educore.notification.NotificationDeepLinkParser
import online.educoreng.educore.notification.NotificationDeepLinkStore
import online.educoreng.educore.presentation.EduCoreFoundationApp
import online.educoreng.educore.update.AppUpdateWorker

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    private val notificationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { granted ->
        // The Application-level immediate update check can run before Android
        // finishes the notification permission flow on a fresh install. Run a
        // second check as soon as permission is granted so a pending app update
        // is surfaced immediately instead of waiting for the periodic worker.
        if (granted) enqueueImmediateUpdateCheck()
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        NotificationDeepLinkStore.publish(NotificationDeepLinkParser.parse(intent))
        requestNotificationPermissionIfNeeded()
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

    private fun enqueueImmediateUpdateCheck() {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
        val request = OneTimeWorkRequestBuilder<AppUpdateWorker>()
            .setConstraints(constraints)
            .build()

        WorkManager.getInstance(this).enqueueUniqueWork(
            "educore_app_update_permission_granted",
            ExistingWorkPolicy.REPLACE,
            request,
        )
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (
            Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }
}
