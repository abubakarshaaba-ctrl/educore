package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCoreMetricCard
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSegmentedControl
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.TransportManifestItemDto
import online.educoreng.educore.core.network.dto.TransportRouteDto
import online.educoreng.educore.core.network.dto.TransportStudentDto

@Composable
internal fun NativeTransportScreen(
    state: TransportUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onOpenManifest: (Long) -> Unit,
    onCloseManifest: () -> Unit,
    onOpenAssignment: (Long?) -> Unit,
    onCloseAssignment: () -> Unit,
    onRoute: (Long?) -> Unit,
    onStudent: (Long?) -> Unit,
    onPickupStop: (String) -> Unit,
    onDirection: (String) -> Unit,
    onAssign: () -> Unit,
    onUnassign: (Long) -> Unit,
    onLoadMore: () -> Unit,
    onRetry: () -> Unit,
) {
    if (state.assignmentOpen) {
        TransportAssignmentScreen(
            state = state,
            onBack = onCloseAssignment,
            onQuery = onQuery,
            onSearch = onSearch,
            onRoute = onRoute,
            onStudent = onStudent,
            onPickupStop = onPickupStop,
            onDirection = onDirection,
            onAssign = onAssign,
            onLoadMore = onLoadMore,
        )
        return
    }

    state.manifest?.let {
        TransportManifestScreen(
            state = state,
            onBack = onCloseManifest,
            onAssign = { onOpenAssignment(it.route.id) },
            onUnassign = onUnassign,
        )
        return
    }

    if (state.isLoading && state.dashboard == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading transport operations")
        return
    }

    val dashboard = state.dashboard
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = "Transport",
                subtitle = "Routes, vehicles, rider capacity and assignments",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }

        dashboard?.let { data ->
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Routes", data.metrics.routes.toString(), Modifier.weight(1f), tone = EduCoreTone.Brand)
                    EduCoreMetricCard("Active buses", data.metrics.activeBuses.toString(), Modifier.weight(1f), tone = EduCoreTone.Info)
                }
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Assigned riders", data.metrics.assignedStudents.toString(), Modifier.weight(1f), tone = EduCoreTone.Success)
                    EduCoreMetricCard("Unassigned", data.metrics.unassignedStudents.toString(), Modifier.weight(1f), tone = EduCoreTone.Warning)
                }
            }
        }

        if (state.canManage) {
            item {
                EduCorePrimaryButton(
                    text = "Assign student to route",
                    onClick = { onOpenAssignment(null) },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }

        item { Text("Routes", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold) }
        if (dashboard?.routes.isNullOrEmpty()) {
            item { EduCoreEmptyState("No transport routes", "Configured school transport routes will appear here.") }
        } else {
            items(dashboard?.routes.orEmpty(), key = TransportRouteDto::id) { route ->
                TransportRouteManagementCard(route = route, onClick = { onOpenManifest(route.id) })
            }
        }

        if (state.errorMessage != null && dashboard == null) {
            item { EduCoreSecondaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
        }
    }
}

@Composable
private fun TransportRouteManagementCard(route: TransportRouteDto, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(route.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text("${route.bus} · ${route.driver}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                EduCoreStatusBadge(if (route.active) "Active" else "Inactive", if (route.active) EduCoreTone.Success else EduCoreTone.Neutral)
            }
            Text(
                "${route.riders} rider${if (route.riders == 1) "" else "s"} · Capacity ${route.capacity ?: 0}",
                style = MaterialTheme.typography.bodyMedium,
            )
            if (!route.morningTime.isNullOrBlank() || !route.eveningTime.isNullOrBlank()) {
                Text(
                    "Morning ${route.morningTime ?: "—"} · Evening ${route.eveningTime ?: "—"}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun TransportManifestScreen(
    state: TransportUiState,
    onBack: () -> Unit,
    onAssign: () -> Unit,
    onUnassign: (Long) -> Unit,
) {
    val manifest = state.manifest ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCorePageHeader(
                title = manifest.route.name,
                subtitle = "Route manifest and rider assignments",
                onBack = onBack,
            )
        }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }
        if (state.canManage) {
            item { EduCorePrimaryButton("Add rider", onAssign, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        }
        if (manifest.manifest.isEmpty()) {
            item { EduCoreEmptyState("No riders assigned", "Students assigned to this route will appear here.") }
        } else {
            items(manifest.manifest, key = TransportManifestItemDto::assignmentId) { rider ->
                TransportRiderCard(rider, state.canManage, state.isSaving, onUnassign)
            }
        }
    }
}

@Composable
private fun TransportRiderCard(
    rider: TransportManifestItemDto,
    canManage: Boolean,
    busy: Boolean,
    onUnassign: (Long) -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Text(rider.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Text(
                listOfNotNull(rider.admissionNumber, rider.className).joinToString(" · "),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCoreStatusBadge(rider.direction.replaceFirstChar(Char::uppercase), EduCoreTone.Info)
                rider.pickupStop?.takeIf(String::isNotBlank)?.let { EduCoreStatusBadge(it, EduCoreTone.Neutral) }
            }
            if (canManage) {
                EduCoreSecondaryButton(
                    text = "Remove from route",
                    onClick = { onUnassign(rider.studentId) },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !busy,
                )
            }
        }
    }
}

@Composable
private fun TransportAssignmentScreen(
    state: TransportUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onRoute: (Long?) -> Unit,
    onStudent: (Long?) -> Unit,
    onPickupStop: (String) -> Unit,
    onDirection: (String) -> Unit,
    onAssign: () -> Unit,
    onLoadMore: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Assign Transport", "Select route, rider, direction and pickup stop", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item { Text("Route", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold) }
        if (state.activeRoutes.isEmpty()) {
            item { EduCoreEmptyState("No active routes", "Activate a transport route before assigning students.") }
        } else {
            items(state.activeRoutes, key = { "assign-route-${it.id}" }) { route ->
                SelectableTransportCard(
                    title = route.name,
                    subtitle = "${route.bus} · ${route.riders}/${route.capacity ?: 0} riders",
                    selected = state.selectedRouteId == route.id,
                    onClick = { onRoute(route.id) },
                )
            }
        }

        item { Text("Student", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold) }
        item { EduCoreSearchBar(state.query, onQuery, placeholder = "Search unassigned student") }
        item { EduCoreSecondaryButton("Search students", onSearch, Modifier.fillMaxWidth(), enabled = !state.isLoading) }
        if (state.unassigned.isEmpty() && !state.isLoading) {
            item { EduCoreEmptyState("No unassigned students", "All active students may already have transport assignments.") }
        } else {
            items(state.unassigned, key = TransportStudentDto::id) { student ->
                SelectableTransportCard(
                    title = student.name,
                    subtitle = listOfNotNull(student.admissionNumber, student.className).joinToString(" · "),
                    selected = state.selectedStudentId == student.id,
                    onClick = { onStudent(student.id) },
                )
            }
        }
        if (state.hasMore) {
            item { EduCoreSecondaryButton(if (state.isLoadingMore) "Loading…" else "Load more", onLoadMore, Modifier.fillMaxWidth(), enabled = !state.isLoadingMore) }
        }

        item { Text("Journey", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold) }
        item {
            EduCoreSegmentedControl(
                options = listOf("Both", "Morning", "Evening"),
                selectedIndex = when (state.direction) { "morning" -> 1; "evening" -> 2; else -> 0 },
                onSelected = { onDirection(listOf("both", "morning", "evening")[it]) },
                enabled = !state.isSaving,
            )
        }
        item {
            EduCoreTextField(
                value = state.pickupStop,
                onValueChange = onPickupStop,
                label = "Pickup stop (optional)",
                modifier = Modifier.fillMaxWidth(),
                enabled = !state.isSaving,
            )
        }
        item {
            EduCorePrimaryButton(
                text = "Save assignment",
                onClick = onAssign,
                modifier = Modifier.fillMaxWidth(),
                enabled = state.canAssign,
                loading = state.isSaving,
            )
        }
    }
}

@Composable
private fun SelectableTransportCard(
    title: String,
    subtitle: String,
    selected: Boolean,
    onClick: () -> Unit,
) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = if (selected) EduCoreColors.Info100 else EduCoreColors.White),
        border = BorderStroke(1.dp, if (selected) EduCoreColors.Info700 else EduCoreColors.Line200),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(EduCoreSpacing.Md),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Column(Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                if (subtitle.isNotBlank()) Text(subtitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            if (selected) EduCoreStatusBadge("Selected", EduCoreTone.Info)
        }
    }
}
