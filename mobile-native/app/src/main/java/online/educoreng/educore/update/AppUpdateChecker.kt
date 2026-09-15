package online.educoreng.educore.update

import android.content.Context
import online.educoreng.educore.BuildConfig
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

/**
 * Lightweight production-update discovery for APK installs distributed outside
 * Google Play. GitHub Releases is already the canonical signed-artifact source
 * used by EduCore's production pipeline, while users download through the
 * stable EduCore web endpoint.
 */
data class AppUpdateInfo(
    val latestVersionCode: Int,
    val latestVersionName: String,
    val minimumSupportedVersionCode: Int,
    val releaseNotes: String,
    val downloadUrl: String,
) {
    val isUpdateAvailable: Boolean
        get() = latestVersionCode > BuildConfig.VERSION_CODE

    val isRequired: Boolean
        get() = BuildConfig.VERSION_CODE < minimumSupportedVersionCode
}

object AppUpdateChecker {
    private const val RELEASE_API =
        "https://api.github.com/repos/abubakarshaaba-ctrl/educore/releases/latest"
    private const val DOWNLOAD_URL = "https://educoreng.online/download/app"
    private const val PREFS = "educore_app_update"
    private const val LAST_CHECK_AT = "last_check_at"
    private const val CHECK_INTERVAL_MS = 12L * 60L * 60L * 1000L

    suspend fun check(context: Context, forceNetworkCheck: Boolean = false): AppUpdateInfo? =
        withContext(Dispatchers.IO) {
            val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            val now = System.currentTimeMillis()
            val lastCheck = prefs.getLong(LAST_CHECK_AT, 0L)
            if (!forceNetworkCheck && now - lastCheck < CHECK_INTERVAL_MS) {
                return@withContext null
            }

            runCatching {
                val connection = (URL(RELEASE_API).openConnection() as HttpURLConnection).apply {
                    requestMethod = "GET"
                    connectTimeout = 8_000
                    readTimeout = 8_000
                    setRequestProperty("Accept", "application/vnd.github+json")
                    setRequestProperty("User-Agent", "EduCore-Android/${BuildConfig.VERSION_NAME}")
                }

                connection.useConnection { conn ->
                    if (conn.responseCode !in 200..299) return@runCatching null
                    val payload = conn.inputStream.bufferedReader().use { it.readText() }
                    val json = JSONObject(payload)
                    val tag = json.optString("tag_name")
                    val match = RELEASE_TAG.matchEntire(tag) ?: return@runCatching null
                    val versionName = match.groupValues[1]
                    val versionCode = match.groupValues[2].toIntOrNull() ?: return@runCatching null
                    val body = json.optString("body")
                    val minimum = MIN_VERSION.find(body)
                        ?.groupValues
                        ?.getOrNull(1)
                        ?.toIntOrNull()
                        ?: 1
                    val notes = WHATS_NEW.find(body)
                        ?.groupValues
                        ?.getOrNull(1)
                        ?.trim()
                        ?.takeIf { it.isNotBlank() }
                        ?: "A newer EduCore version is available with the latest improvements and fixes."

                    AppUpdateInfo(
                        latestVersionCode = versionCode,
                        latestVersionName = versionName,
                        minimumSupportedVersionCode = minimum,
                        releaseNotes = notes,
                        downloadUrl = DOWNLOAD_URL,
                    )
                }
            }.getOrNull().also {
                // Network failures are deliberately not cached, so the next launch can retry.
                if (it != null) prefs.edit().putLong(LAST_CHECK_AT, now).apply()
            }
        }

    private inline fun <T> HttpURLConnection.useConnection(block: (HttpURLConnection) -> T): T =
        try {
            block(this)
        } finally {
            disconnect()
        }

    private val RELEASE_TAG = Regex("^android-v(.+)-code(\\d+)-b\\d+-[A-Za-z0-9._-]+$")
    private val MIN_VERSION = Regex("(?im)^-?\\s*minimumSupportedVersionCode:\\s*(\\d+)\\s*$")
    private val WHATS_NEW = Regex("(?is)##\\s*What's new\\s*(.+?)(?=\\n##\\s|\\z)")
}
