package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
fun EduCorePrimaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    loading: Boolean = false,
    leadingIcon: (@Composable RowScope.() -> Unit)? = null,
) {
    Button(
        onClick = onClick,
        enabled = enabled && !loading,
        modifier = modifier.defaultMinSize(minHeight = EduCoreSizes.TouchTarget),
        colors = ButtonDefaults.buttonColors(
            containerColor = EduCoreColors.Navy900,
            contentColor = Color.White,
        ),
    ) {
        if (loading) {
            CircularProgressIndicator(
                color = Color.White,
                strokeWidth = 2.dp,
                modifier = Modifier.defaultMinSize(minWidth = 18.dp, minHeight = 18.dp),
            )
        } else {
            leadingIcon?.invoke(this)
            Text(text)
        }
    }
}

@Composable
fun EduCoreSecondaryButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    OutlinedButton(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier.defaultMinSize(minHeight = EduCoreSizes.TouchTarget),
    ) {
        Text(text)
    }
}

@Composable
fun EduCoreDangerButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    Button(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier.defaultMinSize(minHeight = EduCoreSizes.TouchTarget),
        colors = ButtonDefaults.buttonColors(
            containerColor = EduCoreColors.Danger600,
            contentColor = Color.White,
        ),
    ) {
        Text(text)
    }
}

@Composable
fun EduCoreTextButton(
    text: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    TextButton(
        onClick = onClick,
        enabled = enabled,
        modifier = modifier.defaultMinSize(minHeight = EduCoreSizes.TouchTarget),
    ) {
        Text(text)
    }
}

fun Modifier.eduCoreFullWidth(): Modifier = fillMaxWidth()

@Composable
fun EduCoreResponsiveButtonPair(
    primaryText: String,
    onPrimary: () -> Unit,
    secondaryText: String,
    onSecondary: () -> Unit,
    modifier: Modifier = Modifier,
    primaryEnabled: Boolean = true,
    secondaryEnabled: Boolean = true,
    primaryLoading: Boolean = false,
) {
    BoxWithConstraints(modifier.fillMaxWidth()) {
        if (maxWidth < 380.dp) {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCorePrimaryButton(primaryText, onPrimary, Modifier.fillMaxWidth(), primaryEnabled, primaryLoading)
                EduCoreSecondaryButton(secondaryText, onSecondary, Modifier.fillMaxWidth(), secondaryEnabled)
            }
        } else {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md)) {
                EduCoreSecondaryButton(secondaryText, onSecondary, Modifier.weight(1f), secondaryEnabled)
                EduCorePrimaryButton(primaryText, onPrimary, Modifier.weight(1f), primaryEnabled, primaryLoading)
            }
        }
    }
}
