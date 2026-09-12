package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.PrimaryScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
fun EduCoreTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    readOnly: Boolean = false,
    singleLine: Boolean = true,
    supportingText: String? = null,
    error: String? = null,
    visualTransformation: VisualTransformation = VisualTransformation.None,
    keyboardOptions: KeyboardOptions = KeyboardOptions.Default,
    keyboardActions: KeyboardActions = KeyboardActions.Default,
    leadingIcon: (@Composable () -> Unit)? = null,
    trailingIcon: (@Composable () -> Unit)? = null,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = modifier,
        enabled = enabled,
        readOnly = readOnly,
        singleLine = singleLine,
        isError = error != null,
        supportingText = when {
            error != null -> ({ Text(error) })
            supportingText != null -> ({ Text(supportingText) })
            else -> null
        },
        visualTransformation = visualTransformation,
        keyboardOptions = keyboardOptions,
        keyboardActions = keyboardActions,
        leadingIcon = leadingIcon,
        trailingIcon = trailingIcon,
    )
}

@Composable
fun EduCoreSearchBar(
    value: String,
    onValueChange: (String) -> Unit,
    modifier: Modifier = Modifier,
    placeholder: String = "Search",
    enabled: Boolean = true,
) {
    val focusManager = LocalFocusManager.current
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = modifier.fillMaxWidth().height(48.dp),
        enabled = enabled,
        singleLine = true,
        textStyle = MaterialTheme.typography.bodyMedium,
        placeholder = { Text(placeholder, style = MaterialTheme.typography.bodyMedium) },
        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
        trailingIcon = if (value.isEmpty()) {
            null
        } else {
            {
                IconButton(onClick = { onValueChange("") }) {
                    Icon(Icons.Default.Clear, contentDescription = "Clear search")
                }
            }
        },
        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
        keyboardActions = KeyboardActions(onSearch = { focusManager.clearFocus() }),
    )
}

@Composable
fun EduCoreFilterChip(
    label: String,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    FilterChip(
        selected = selected,
        onClick = onClick,
        label = { Text(label) },
        modifier = modifier,
        enabled = enabled,
    )
}

@Composable
fun EduCoreSegmentedControl(
    options: List<String>,
    selectedIndex: Int,
    onSelected: (Int) -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
) {
    Row(
        modifier = modifier.horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        options.forEachIndexed { index, option ->
            EduCoreFilterChip(
                label = option,
                selected = index == selectedIndex,
                onClick = { onSelected(index) },
                enabled = enabled,
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EduCoreTabs(
    labels: List<String>,
    selectedIndex: Int,
    onSelected: (Int) -> Unit,
    modifier: Modifier = Modifier,
) {
    if (labels.isEmpty()) return
    PrimaryScrollableTabRow(
        selectedTabIndex = selectedIndex.coerceIn(0, (labels.lastIndex).coerceAtLeast(0)),
        modifier = modifier,
        containerColor = EduCoreColors.White,
        contentColor = EduCoreColors.Navy900,
        edgePadding = EduCoreSpacing.Sm,
        divider = {},
    ) {
        labels.forEachIndexed { index, label ->
            Tab(
                selected = selectedIndex == index,
                onClick = { onSelected(index) },
                text = { Text(label, style = MaterialTheme.typography.labelLarge) },
            )
        }
    }
}

@Composable
fun EduCoreScoreField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    maximum: String,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    locked: Boolean = false,
    error: String? = null,
    onNext: (() -> Unit)? = null,
) {
    EduCoreTextField(
        value = value,
        onValueChange = { candidate ->
            if (candidate.isEmpty() || candidate.matches(Regex("\\d{0,3}(\\.\\d{0,2})?"))) {
                onValueChange(candidate)
            }
        },
        label = label,
        modifier = modifier,
        enabled = enabled,
        readOnly = locked,
        supportingText = "/$maximum",
        error = error,
        keyboardOptions = KeyboardOptions(
            keyboardType = KeyboardType.Decimal,
            imeAction = if (onNext == null) ImeAction.Done else ImeAction.Next,
        ),
        keyboardActions = KeyboardActions(
            onNext = { onNext?.invoke() },
            onDone = { onNext?.invoke() },
        ),
        trailingIcon = if (locked) {
            { Icon(Icons.Default.Lock, contentDescription = "Score locked") }
        } else {
            null
        },
    )
}
