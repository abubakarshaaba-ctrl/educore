package online.educoreng.educore.presentation

import android.graphics.BitmapFactory
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyGridScope
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import java.util.Calendar
import online.educoreng.educore.core.designsystem.component.EduCoreProfileHeader
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * Home is intentionally tile-free. Functional modules live in the bottom tabs
 * and More workspace; the home screen only identifies the signed-in user.
 */
internal fun LazyGridScope.dashboardHomeContent(
    session: SessionSnapshot,
    state: DashboardUiState,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onRetry: () -> Unit,
) {
    item(key = "profile", span = { GridItemSpan(maxLineSpan) }) {
        val photoBitmap = remember(state.staffPhoto) {
            state.staffPhoto?.let { bytes ->
                runCatching { BitmapFactory.decodeByteArray(bytes, 0, bytes.size) }.getOrNull()
            }
        }
        EduCoreProfileHeader(
            name = "${timeGreeting()}, ${session.user.name}",
            role = session.user.roleLabel,
            identifier = session.user.staffId ?: session.user.email,
            modifier = Modifier.fillMaxWidth(),
            avatar = photoBitmap?.let { bitmap ->
                {
                    Image(
                        bitmap = bitmap.asImageBitmap(),
                        contentDescription = "${session.user.name} profile photo",
                        modifier = Modifier
                            .size(EduCoreSizes.LargeAvatar)
                            .clip(MaterialTheme.shapes.large),
                        contentScale = ContentScale.Crop,
                    )
                }
            },
        )
    }
}

private fun timeGreeting(): String = when (Calendar.getInstance().get(Calendar.HOUR_OF_DAY)) {
    in 5..11 -> "Good morning"
    in 12..16 -> "Good afternoon"
    else -> "Good evening"
}
