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
}
