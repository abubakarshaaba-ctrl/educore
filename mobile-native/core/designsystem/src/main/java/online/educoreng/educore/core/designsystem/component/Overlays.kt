package online.educoreng.educore.core.designsystem.component

import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Text
import androidx.compose.material3.TimePicker
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.material3.rememberTimePickerState
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing

@Composable
fun EduCoreConfirmationDialog(
    visible: Boolean,
    title: String,
    message: String,
    confirmLabel: String,
    onConfirm: () -> Unit,
    onDismiss: () -> Unit,
    destructive: Boolean = false,
) {
    if (!visible) return
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(title) },
        text = { Text(message) },
        confirmButton = {
            if (destructive) {
                EduCoreDangerButton(confirmLabel, onConfirm)
            } else {
                EduCorePrimaryButton(confirmLabel, onConfirm)
            }
        },
        dismissButton = { EduCoreTextButton("Cancel", onDismiss) },
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EduCoreBottomSheet(
    visible: Boolean,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier,
    content: @Composable ColumnScope.() -> Unit,
) {
    if (!visible) return
    val state = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    ModalBottomSheet(
        onDismissRequest = onDismiss,
        modifier = modifier,
        sheetState = state,
    ) {
        androidx.compose.foundation.layout.Column(
            modifier = Modifier.fillMaxWidth().padding(
                start = EduCoreSpacing.Lg,
                end = EduCoreSpacing.Lg,
                bottom = EduCoreSpacing.Xxl,
            ),
            content = content,
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EduCoreDatePicker(
    visible: Boolean,
    onDateSelected: (Long) -> Unit,
    onDismiss: () -> Unit,
    initialDateMillis: Long? = null,
) {
    if (!visible) return
    val state = rememberDatePickerState(initialSelectedDateMillis = initialDateMillis)
    DatePickerDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            EduCoreTextButton(
                text = "Select",
                onClick = {
                    state.selectedDateMillis?.let(onDateSelected)
                    onDismiss()
                },
                enabled = state.selectedDateMillis != null,
            )
        },
        dismissButton = { EduCoreTextButton("Cancel", onDismiss) },
    ) {
        DatePicker(state = state)
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EduCoreTimePicker(
    visible: Boolean,
    initialHour: Int,
    initialMinute: Int,
    onTimeSelected: (hour: Int, minute: Int) -> Unit,
    onDismiss: () -> Unit,
) {
    if (!visible) return
    val state = rememberTimePickerState(
        initialHour = initialHour.coerceIn(0, 23),
        initialMinute = initialMinute.coerceIn(0, 59),
        is24Hour = true,
    )
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Select time") },
        text = { TimePicker(state = state) },
        confirmButton = {
            EduCorePrimaryButton(
                text = "Select",
                onClick = {
                    onTimeSelected(state.hour, state.minute)
                    onDismiss()
                },
            )
        },
        dismissButton = { EduCoreTextButton("Cancel", onDismiss) },
    )
}
