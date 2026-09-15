package online.educoreng.educore

import android.app.Application
import com.google.firebase.messaging.FirebaseMessaging
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class EduCoreApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Release notifications are intentionally independent of tenant/user
        // authentication. A successful production release can therefore notify
        // every installed EduCore app, including devices that are currently
        // signed out, without weakening the authenticated in-app push contract.
        FirebaseMessaging.getInstance()
            .subscribeToTopic(APP_UPDATES_TOPIC)
    }

    private companion object {
        const val APP_UPDATES_TOPIC = "educore_app_updates"
    }
}
