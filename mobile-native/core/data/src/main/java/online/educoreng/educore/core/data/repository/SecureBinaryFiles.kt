package online.educoreng.educore.core.data.repository

import android.content.Context
import android.os.Environment
import androidx.core.content.FileProvider
import java.io.ByteArrayOutputStream
import java.io.File
import java.io.IOException
import okhttp3.ResponseBody
import online.educoreng.educore.core.model.DownloadedDocument

internal fun ResponseBody.readByteArrayBounded(maxBytes: Int): ByteArray = use { body ->
    require(maxBytes > 0) { "The byte limit must be positive." }
    val declaredLength = body.contentLength()
    if (declaredLength > maxBytes) throw IOException("The remote file exceeds the permitted size.")

    body.byteStream().use { input ->
        val initialSize = declaredLength.takeIf { it in 1..maxBytes.toLong() }?.toInt() ?: DEFAULT_BUFFER_SIZE
        val output = ByteArrayOutputStream(initialSize)
        val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
        var total = 0
        while (true) {
            val count = input.read(buffer)
            if (count < 0) break
            total += count
            if (total > maxBytes) throw IOException("The remote file exceeds the permitted size.")
            output.write(buffer, 0, count)
        }
        output.toByteArray()
    }
}

/**
 * Saves a remote document inside EduCore's private downloads directory and
 * returns a FileProvider URI that can be opened through the app's native
 * document workflow. The filename is sanitized and path traversal is blocked.
 */
fun saveDownloadedDocument(
    context: Context,
    body: ResponseBody,
    requestedName: String,
    requestedMimeType: String,
): DownloadedDocument {
    val directory = requireNotNull(context.getExternalFilesDir(Environment.DIRECTORY_DOWNLOADS)).canonicalFile
    check(directory.exists() || directory.mkdirs()) { "The private download directory is unavailable." }

    val safeName = sanitizeFilename(requestedName)
    val finalFile = uniqueFile(directory, safeName)
    check(finalFile.canonicalFile.parentFile == directory) { "The download path is outside the private directory." }
    val partialFile = File.createTempFile(".educore-", ".part", directory)

    try {
        body.use { response ->
            response.byteStream().use { input ->
                partialFile.outputStream().buffered().use { output -> input.copyTo(output, DOWNLOAD_BUFFER_SIZE) }
            }
        }
        if (!partialFile.renameTo(finalFile)) {
            partialFile.copyTo(finalFile, overwrite = false)
            check(partialFile.delete()) { "The temporary download could not be removed." }
        }
    } catch (error: Throwable) {
        partialFile.delete()
        finalFile.delete()
        throw error
    }

    val mimeType = requestedMimeType.takeIf { MIME_TYPE.matches(it) } ?: "application/octet-stream"
    val uri = FileProvider.getUriForFile(context, "${context.packageName}.files", finalFile)
    return DownloadedDocument(uri.toString(), finalFile.name, mimeType)
}

internal fun sanitizeFilename(value: String): String {
    val cleaned = value.substringAfterLast('/').substringAfterLast('\\')
        .replace(Regex("[^A-Za-z0-9._ -]"), "_")
        .trim(' ', '.')
        .take(180)
    return cleaned.ifBlank { "educore-document.bin" }
}

private fun uniqueFile(directory: File, requestedName: String): File {
    val requested = File(directory, requestedName)
    if (!requested.exists()) return requested
    val extension = requestedName.substringAfterLast('.', "").takeIf(String::isNotBlank)?.let { ".$it" }.orEmpty()
    val stem = requestedName.removeSuffix(extension).take(145)
    var suffix = 2
    while (suffix < 10_000) {
        val candidate = File(directory, "$stem ($suffix)$extension")
        if (!candidate.exists()) return candidate
        suffix++
    }
    return File(directory, "$stem-${System.currentTimeMillis()}$extension")
}

private const val DOWNLOAD_BUFFER_SIZE = 64 * 1024
private val MIME_TYPE = Regex("^[A-Za-z0-9][A-Za-z0-9.+_-]*/[A-Za-z0-9][A-Za-z0-9.+_-]*$")
