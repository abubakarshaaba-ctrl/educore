package online.educoreng.educore.presentation

import android.app.TimePickerDialog
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Button
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
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
import java.util.Locale

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

    LaunchedEffect(capturedLatitude, capturedLongitude) {
        if (capturedLatitude != null && capturedLongitude != null) {
            latitude = String.format(Locale.US, "%.6f", capturedLatitude)
            longitude = String.format(Locale.US, "%.6f", capturedLongitude)
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
    val resumptionValid = parseAttendanceTime(resumption) != null
    val closingValid = parseAttendanceTime(closing) != null
    val valid = latValid && lngValid && radiusValid && resumptionValid && closingValid

    fun pickTime(current: String, onPicked: (String) -> Unit) {
        val parsed = parseAttendanceTime(current) ?: LocalTime.now()
        TimePickerDialog(
            context,
            { _, hour, minute -> onPicked(String.format(Locale.US, "%02d:%02d", hour, minute)) },
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
            OutlinedButton(
                onClick = { pickTime(resumption) { resumption = it } },
                enabled = !state.isMutating,
                modifier = Modifier.fillMaxWidth(),
            ) { Text(if (resumptionValid) "Resumption time · $resumption" else "Select resumption time") }
        }

        item {
            OutlinedButton(
                onClick = { pickTime(closing) { closing = it } },
                enabled = !state.isMutating,
                modifier = Modifier.fillMaxWidth(),
            ) { Text(if (closingValid) "Closing time · $closing" else "Select closing time") }
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
                            latitude.isBlank() -> Text("Required · -90 to 90")
                            !latValid -> Text("Enter -90 to 90")
                        }
                    },
                    isError = latitude.isNotBlank() && !latValid,
                    singleLine = true,
                    modifier = Modifier.weight(1f),
                )
                OutlinedTextField(
                    value = longitude,
                    onValueChange = { longitude = it.take(17) },
                    label = { Text("Longitude") },
                    supportingText = {
                        when {
                            longitude.isBlank() -> Text("Required · -180 to 180")
                            !lngValid -> Text("Enter -180 to 180")
                        }
                    },
                    isError = longitude.isNotBlank() && !lngValid,
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
                        radius.isBlank() -> Text("Required · positive number")
                        !radiusValid -> Text("Radius must be positive")
                        capturedAccuracyMetres != null -> Text("Location accuracy ±${capturedAccuracyMetres.toInt()} m")
                    }
                },
                isError = radius.isNotBlank() && !radiusValid,
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
        }

        if (!valid) {
            item {
                Text(
                    "Save becomes available when resumption time, closing time, latitude, longitude and radius are valid.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }

        item {
            Button(
                onClick = {
                    if (valid) {
                        onSave(resumption, graceValue, closing, geoEnabled, latValue, lngValue, radiusValue)
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
                Text(message, style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Success700)
            }
        }
    }
}

private fun parseAttendanceTime(value: String): LocalTime? = runCatching {
    LocalTime.parse(value.take(5), DateTimeFormatter.ofPattern("HH:mm"))
}.getOrNull()
