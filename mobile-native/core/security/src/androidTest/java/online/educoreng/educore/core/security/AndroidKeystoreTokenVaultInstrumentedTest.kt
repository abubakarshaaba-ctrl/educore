package online.educoreng.educore.core.security

import android.content.Context
import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith

@RunWith(AndroidJUnit4::class)
class AndroidKeystoreTokenVaultInstrumentedTest {
    private lateinit var context: Context
    private lateinit var vault: AndroidKeystoreTokenVault

    @Before
    fun setUp() {
        context = InstrumentationRegistry.getInstrumentation().targetContext
        vault = AndroidKeystoreTokenVault(context)
        vault.clear()
    }

    @After
    fun tearDown() {
        vault.clear()
    }

    @Test
    fun legacyFlutterTokenIsEncryptedAndPlaintextSessionIsRemoved() {
        val legacy = context.getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        legacy.edit()
            .putString("flutter.token", "legacy-bearer-token")
            .putString("flutter.user", "{\"id\":1}")
            .putString("flutter.school", "{\"id\":2}")
            .putStringSet("flutter.permissions", setOf("scores.view"))
            .commit()

        assertEquals("legacy-bearer-token", vault.bearerToken())
        assertNull(legacy.getString("flutter.token", null))
        assertFalse(legacy.contains("flutter.user"))
        assertFalse(legacy.contains("flutter.school"))
        assertFalse(legacy.contains("flutter.permissions"))
        assertEquals("legacy-bearer-token", AndroidKeystoreTokenVault(context).bearerToken())
    }

    @Test
    fun logoutClearsEncryptedAndLegacySessionValues() {
        val legacy = context.getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        vault.save("native-bearer-token")
        legacy.edit().putString("flutter.token", "stale-legacy-token").commit()

        vault.clear()

        assertNull(vault.bearerToken())
        assertNull(legacy.getString("flutter.token", null))
    }
}
