package online.educoreng.educore.update

import java.net.HttpURLConnection
import java.net.URL
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import online.educoreng.educore.BuildConfig
import org.json.JSONObject

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
    private const val RELEASE_API = "https://educoreng.online/api/v1/mobile-release/latest"
    private const val DOWNLOAD_URL = "https://educoreng.online/download/app"

    suspend fun check(): AppUpdateInfo? = withContext(Dispatchers.IO) {
        runCatching {
            val connection = (URL(RELEASE_API).openConnection() as HttpURLConnection).apply {
                requestMethod = "GET"
                connectTimeout = 8_000
                readTimeout = 8_000
                useCaches = false
                setRequestProperty("Accept", "application/json")
                setRequestProperty("Cache-Control", "no-cache")
                setRequestProperty("User-Agent", "EduCore-Android/${BuildConfig.VERSION_NAME}")
            }

            connection.useConnection { conn ->
                if (conn.responseCode !in 200..299) return@runCatching null

                val envelope = JSONObject(conn.inputStream.bufferedReader().use { it.readText() })
                if (!envelope.optBoolean("available", false)) return@runCatching null
                val release = envelope.optJSONObject("release") ?: return@runCatching null

                val versionCode = release.optInt("version_code", 0)
                if (versionCode <= BuildConfig.VERSION_CODE) return@runCatching null

                AppUpdateInfo(
                    latestVersionCode = versionCode,
                    latestVersionName = release.optString("version_name").trim().ifBlank { versionCode.toString() },
                    minimumSupportedVersionCode = release.optInt("minimum_supported_version_code", 1).coerceAtLeast(1),
                    releaseNotes = release.optString("message").trim().ifBlank {
                        "A newer EduCore version is available with the latest improvements and fixes."
                    },
                    downloadUrl = release.optString("download_url").trim()
                        .takeIf { it.startsWith("https://", ignoreCase = true) }
                        ?: DOWNLOAD_URL,
                )
            }
        }.getOrNull()
    }

    private inline fun <T> HttpURLConnection.useConnection(block: (HttpURLConnection) -> T): T =
        try {
            block(this)
        } finally {
            disconnect()
        }
}
