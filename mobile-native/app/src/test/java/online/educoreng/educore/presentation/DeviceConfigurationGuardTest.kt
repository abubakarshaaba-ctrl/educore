package online.educoreng.educore.presentation

import java.io.File
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class DeviceConfigurationGuardTest {
    private val manifestText: String by lazy {
        File("src/main/AndroidManifest.xml").readText()
    }

    @Test
    fun main_activity_remains_resizable_and_keyboard_safe() {
        val mainActivity = manifestText
            .substringAfter("<activity\n            android:name=\".MainActivity\"")
            .substringBefore("</activity>")

        assertTrue(mainActivity.contains("android:resizeableActivity=\"true\""))
        assertTrue(mainActivity.contains("android:windowSoftInputMode=\"adjustResize\""))
        assertFalse(mainActivity.contains("android:screenOrientation="))
    }

    @Test
    fun only_camera_capture_is_intentionally_portrait_locked() {
        assertTrue(manifestText.contains("android:name=\".PortraitCaptureActivity\""))
        assertTrue(manifestText.contains("android:screenOrientation=\"portrait\""))
    }

    @Test
    fun production_app_disallows_cleartext_and_android_backup() {
        assertTrue(manifestText.contains("android:usesCleartextTraffic=\"false\""))
        assertTrue(manifestText.contains("android:allowBackup=\"false\""))
    }

    @Test
    fun camera_and_notification_capabilities_remain_declared_without_requiring_camera_hardware() {
        assertTrue(manifestText.contains("android.permission.CAMERA"))
        assertTrue(manifestText.contains("android.permission.POST_NOTIFICATIONS"))
        assertTrue(manifestText.contains("android.hardware.camera"))
        assertTrue(manifestText.contains("android:required=\"false\""))
    }

    @Test
    fun downloaded_documents_and_push_deep_links_keep_required_android_components() {
        assertTrue(manifestText.contains("androidx.core.content.FileProvider"))
        assertTrue(manifestText.contains("android:grantUriPermissions=\"true\""))
        assertTrue(manifestText.contains(".notification.EduCoreMessagingService"))
        assertTrue(manifestText.contains("com.google.firebase.MESSAGING_EVENT"))
    }
}
