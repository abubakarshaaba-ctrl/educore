package online.educoreng.educore.presentation

import android.content.ContentResolver
import android.net.Uri
import android.provider.OpenableColumns
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import java.io.ByteArrayOutputStream

internal const val PLATFORM_BROADCAST_IMAGE_MAX_BYTES = 5 * 1024 * 1024

internal data class PlatformBroadcastImage(
    val bytes: ByteArray,
    val fileName: String,
    val mimeType: String,
) {
    val sizeBytes: Int get() = bytes.size
}

@Composable
internal fun PlatformBroadcastImageAttachment(
    image: PlatformBroadcastImage?,
    onImageChanged: (PlatformBroadcastImage?) -> Unit,
    enabled: Boolean,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var errorMessage by remember { mutableStateOf<String?>(null) }
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        if (uri == null) return@rememberLauncherForActivityResult
        errorMessage = null
        scope.launch {
            val result = withContext(Dispatchers.IO) {
                context.contentResolver.readPlatformBroadcastImage(uri)
            }
            result.onSuccess(onImageChanged)
                .onFailure { errorMessage = it.message ?: "Unable to read the selected image." }
        }
    }

    Column(modifier, verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            OutlinedButton(
                onClick = { picker.launch(arrayOf("image/jpeg", "image/png", "image/webp")) },
                enabled = enabled,
            ) {
                Text(if (image == null) "Add image" else "Replace image")
            }
            image?.let { selected ->
                Text(
                    "${selected.fileName} · ${formatImageSize(selected.sizeBytes)}",
                    modifier = Modifier.weight(1f),
                    style = MaterialTheme.typography.bodySmall,
                )
                OutlinedButton(onClick = { onImageChanged(null) }, enabled = enabled) {
                    Text("Remove")
                }
            }
        }
        Text(
            "Optional · JPG, PNG or WebP · maximum 5 MB",
            style = MaterialTheme.typography.bodySmall,
        )
        errorMessage?.let { message ->
            Text(message, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
        }
    }
}

private fun ContentResolver.readPlatformBroadcastImage(uri: Uri): Result<PlatformBroadcastImage> = runCatching {
    val mimeType = getType(uri)?.lowercase()
        ?: throw IllegalArgumentException("Could not determine the image type.")
    require(mimeType in ALLOWED_PLATFORM_BROADCAST_IMAGE_TYPES) {
        "Choose a JPG, PNG or WebP image."
    }

    var fileName = "broadcast-image.${mimeType.defaultImageExtension()}"
    query(uri, arrayOf(OpenableColumns.DISPLAY_NAME, OpenableColumns.SIZE), null, null, null)?.use { cursor ->
        if (cursor.moveToFirst()) {
            val nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            val sizeIndex = cursor.getColumnIndex(OpenableColumns.SIZE)
            if (nameIndex >= 0) cursor.getString(nameIndex)?.takeIf(String::isNotBlank)?.let { fileName = it }
            if (sizeIndex >= 0 && !cursor.isNull(sizeIndex)) {
                require(cursor.getLong(sizeIndex) <= PLATFORM_BROADCAST_IMAGE_MAX_BYTES) {
                    "Image must be 5 MB or smaller."
                }
            }
        }
    }

    val bytes = openInputStream(uri)?.use { input ->
        val output = ByteArrayOutputStream()
        val buffer = ByteArray(8 * 1024)
        var total = 0
        while (true) {
            val count = input.read(buffer)
            if (count < 0) break
            total += count
            require(total <= PLATFORM_BROADCAST_IMAGE_MAX_BYTES) { "Image must be 5 MB or smaller." }
            output.write(buffer, 0, count)
        }
        output.toByteArray()
    } ?: throw IllegalArgumentException("Could not read the selected image.")

    require(bytes.isNotEmpty()) { "The selected image is empty." }
    PlatformBroadcastImage(
        bytes = bytes,
        fileName = fileName.substringAfterLast('/').take(180),
        mimeType = mimeType,
    )
}

private fun String.defaultImageExtension(): String = when (this) {
    "image/png" -> "png"
    "image/webp" -> "webp"
    else -> "jpg"
}

private fun formatImageSize(bytes: Int): String = if (bytes >= 1024 * 1024) {
    String.format("%.1f MB", bytes / (1024f * 1024f))
} else {
    String.format("%.0f KB", bytes / 1024f)
}

private val ALLOWED_PLATFORM_BROADCAST_IMAGE_TYPES = setOf("image/jpeg", "image/png", "image/webp")
