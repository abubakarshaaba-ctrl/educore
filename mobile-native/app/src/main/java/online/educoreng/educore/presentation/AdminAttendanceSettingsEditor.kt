package online.educoreng.educore.presentation

import android.app.TimePickerDialog
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Button
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import java.time.LocalTime
import java.time.format.DateTimeFormatter

@Composable
internal fun AdminAttendanceSettingsEditor(
    state: AdminStaffAttendanceUiState,
    capturedLatitude: Double?,
    capturedLongitude: Double?,
    capturedAccuracyMetres: Float?,
    onSection: (AdminAttendanceSection) -> Unit,
    onSave: (String, Int, String, Boolean, Double?, Double?, Int?) -> Unit,
) {
    val context = LocalContext.current
    val settings = state.snapshot?.settings
    var resumption by remember(settings) { mutableStateOf(settings?.resumptionTime.orEmpty()) }
    var closing by remember(settings) { mutableStateOf(settings?.closingTime.orEmpty()) }
    var grace by remember(settings) { mutableStateOf(settings?.graceMinutes?.toString().orEmpty()) }
    var geoEnabled by remember(settings) { mutableStateOf(settings?.geoEnabled ?: true) }
    var latitude by remember(settings) { mutableStateOf(settings?.geoLat?.toString().orEmpty()) }
    var longitude by remember(settings) { mutableStateOf(settings?.geoLng?.toString().orEmpty()) }
    var radius by remember(settings) { mutableStateOf(settings?.geoRadiusMeters?.toString().orEmpty()) }
    var attemptedSave by remember { mutableStateOf(false) }

    LaunchedEffect(capturedLatitude, capturedLongitude) {
        if (capturedLatitude != null && capturedLongitude != null) {
            latitude = "%.6f".format(capturedLatitude)
            longitude = "%.6f".format(capturedLongitude)
            geoEnabled = true
            if (radius.isBlank()) radius = "500"
        }
    }

    val latValue = latitude.toDoubleOrNull()
    val lngValue = longitude.toDoubleOrNull()
    val radiusValue = radius.toIntOrNull()
    val graceValue = grace.toIntOrNull() ?: 0
    val latValid = latValue != null && latValue in -90.0..90.0
    val lngValid = lngValue != null && lngValue in -180.0..180.0
    val radiusValid = radiusValue != null && radiusValue > 0
    val resumptionValid = parseTime(resumption) != null
    val closingValid = parseTime(closing) != null
    val valid = latValid && lngValid && radiusValid && resumptionValid && closingValid

    fun pickTime(current: String, onPicked: (String) -> Unit) {
        val parsed = parseTime(current) ?: LocalTime.now()
        TimePickerDialog(
            context,
            { _, hour, minute -> onPicked("%02d:%02d".format(hour, minute)) },
            parsed.hour,
            parsed.minute,
            true,
        ).show()
    }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs),
            ) {
                AdminAttendanceSection.entries.forEach { section ->
                    FilterChip(
                        selected = section == AdminAttendanceSection.SETTINGS,
                        onClick = { onSection(section) },
                        label = { Text(section.label, style = MaterialTheme.typography.labelSmall) },
                    )
                }
            }
        }

        item {
            Text("Attendance settings", style = MaterialTheme.typography.titleMedium, color = EduCoreColors.Navy900)
            Text(
                "Configure school hours and the location used for attendance verification.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        item {
            OutlinedTextField(
                value = resumption,
                onValueChange = {},
                readOnly = true,
                label = { Text("Resumption time") },
                supportingText = { if (attemptedSave && !resumptionValid) Text("Select a valid resumption time.") },
                isError = attemptedSave && !resumptionValid,
                trailingIcon = { Text("Select") },
                modifier = Modifier.fillMaxWidth(),
            )
            Button(
                onClick = { pickTime(resumption) { resumption = it } },
                enabled = !state.isMutating,
                modifier = Modifier.fillMaxWidth().padding(top = EduCoreSpacing.Xs),
            ) { Text("Choose resumption time") }
        }

        item {
            OutlinedTextField(
                value = closing,
                onValueChange = {},
                readOnly = true,
                label = { Text("Closing time") },
                supportingText = { if (attemptedSave && !closingValid) Text("Select a valid closing time.") },
                isError = attemptedSave && !closingValid,
                modifier = Modifier.fillMaxWidth(),
            )
            Button(
                onClick = { pickTime(closing) { closing = it } },
                enabled = !state.isMutating,
                modifier = Modifier.fillMaxWidth().padding(top = EduCoreSpacing.Xs),
            ) { Text("Choose closing time") }
        }

        item {
            OutlinedTextField(
                value = grace,
                onValueChange = { grace = it.filter(Char::isDigit).take(3) },
                label = { Text("Grace minutes") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Column(Modifier.weight(1f)) {
                    Text("Geofence", style = MaterialTheme.typography.titleSmall)
                    Text("Require attendance within the configured school radius", style = MaterialTheme.typography.bodySmall)
                }
                Switch(checked = geoEnabled, onCheckedChange = { geoEnabled = it })
            }
        }

        item {
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                OutlinedTextField(
                    value = latitude,
                    onValueChange = { latitude = it.take(16) },
                    label = { Text("Latitude") },
                    supportingText = {
                        when {
                            attemptedSave && latitude.isBlank() -> Text("Latitude is required.")
                            attemptedSave && !latValid -> Text("Enter a value from -90 to 90.")
                        }
                    },
                    isError = attemptedSave && !latValid,
                    singleLine = true,
                    modifier = Modifier.weight(1f),
                )
                OutlinedTextField(
                    value = longitude,
                    onValueChange = { longitude = it.take(17) },
                    label = { Text("Longitude") },
                    supportingText = {
                        when {
                            attemptedSave && longitude.isBlank() -> Text("Longitude is required.")
                            attemptedSave && !lngValid -> Text("Enter a value from -180 to 180.")
                        }
                    },
                    isError = attemptedSave && !lngValid,
                    singleLine = true,
                    modifier = Modifier.weight(1f),
                )
            }
        }

        item {
            OutlinedTextField(
                value = radius,
                onValueChange = { radius = it.filter(Char::isDigit).take(6) },
                label = { Text("Radius (metres)") },
                supportingText = {
                    when {
                        attemptedSave && radius.isBlank() -> Text("Radius is required.")
                        attemptedSave && !radiusValid -> Text("Radius must be a positive number.")
                        capturedAccuracyMetres != null -> Text("Captured location accuracy: ±${capturedAccuracyMetres.toInt()} m")
                    }
                },
                isError = attemptedSave && !radiusValid,
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        item {
            Button(
                onClick = {
                    attemptedSave = true
                    if (valid) {
                        onSave(
                            resumption,
                            graceValue,
                            closing,
                            geoEnabled,
                            latValue,
                            lngValue,
                            radiusValue,
                        )
                    }
                },
                enabled = !state.isMutating && valid,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text(if (state.isMutating) "Saving…" else "Save attendance settings")
            }
        }

        state.message?.let { message ->
            item {
                Text(
                    message,
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Success700,
                )
            }
        }
    }
}

private fun parseTime(value: String): LocalTime? = runCatching {
    LocalTime.parse(value.take(5), DateTimeFormatter.ofPattern("HH:mm"))
}.getOrNull()
