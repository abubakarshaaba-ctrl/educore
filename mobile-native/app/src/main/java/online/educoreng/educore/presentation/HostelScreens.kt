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
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreDangerButton
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
import online.educoreng.educore.core.network.dto.HostelAllocationDto
import online.educoreng.educore.core.network.dto.HostelDto
import online.educoreng.educore.core.network.dto.HostelRoomDto
import online.educoreng.educore.core.network.dto.HostelStudentDto
import online.educoreng.educore.core.network.dto.HostelWardenDto

@Composable
internal fun NativeHostelsScreen(
    state: HostelUiState,
    onBack: () -> Unit,
    onQuery: (String) -> Unit,
    onSearch: () -> Unit,
    onLoadMore: () -> Unit,
    onOpenCreateHostel: () -> Unit,
    onOpenCreateRoom: (Long) -> Unit,
    onOpenAllocation: (Long?) -> Unit,
    onCloseEditor: () -> Unit,
    onHostelName: (String) -> Unit,
    onHostelGender: (String) -> Unit,
    onHostelCapacity: (String) -> Unit,
    onWarden: (Long?) -> Unit,
    onRoomNumber: (String) -> Unit,
    onRoomCapacity: (String) -> Unit,
    onStudentQuery: (String) -> Unit,
    onSearchStudents: () -> Unit,
    onAllocationHostel: (Long?) -> Unit,
    onAllocationRoom: (Long?) -> Unit,
    onAllocationStudent: (Long?) -> Unit,
    onCreateHostel: () -> Unit,
    onCreateRoom: () -> Unit,
    onAllocate: () -> Unit,
    onVacate: (Long) -> Unit,
    onRetry: () -> Unit,
) {
    when (state.editorMode) {
        HostelEditorMode.HOSTEL -> {
            CreateHostelScreen(state, onCloseEditor, onHostelName, onHostelGender, onHostelCapacity, onWarden, onCreateHostel)
            return
        }
        HostelEditorMode.ROOM -> {
            CreateRoomScreen(state, onCloseEditor, onRoomNumber, onRoomCapacity, onCreateRoom)
            return
        }
        HostelEditorMode.ALLOCATION -> {
            AllocateHostelScreen(
                state,
                onCloseEditor,
                onStudentQuery,
                onSearchStudents,
                onAllocationHostel,
                onAllocationRoom,
                onAllocationStudent,
                onAllocate,
            )
            return
        }
        HostelEditorMode.NONE -> Unit
    }

    if (state.isLoading && state.workspace == null) {
        EduCoreLoadingState(Modifier.fillMaxSize(), "Loading hostel operations")
        return
    }

    val workspace = state.workspace
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Hostels", "Boarding capacity, rooms, wardens and resident allocation", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        state.message?.let { item { Text(it, color = EduCoreColors.Success700) } }

        workspace?.let { data ->
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Hostels", data.metrics.hostels.toString(), Modifier.weight(1f), tone = EduCoreTone.Brand)
                    EduCoreMetricCard("Rooms", data.metrics.rooms.toString(), Modifier.weight(1f), tone = EduCoreTone.Info)
                }
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreMetricCard("Capacity", data.metrics.capacity.toString(), Modifier.weight(1f), tone = EduCoreTone.Info)
                    EduCoreMetricCard("Residents", data.metrics.residents.toString(), Modifier.weight(1f), tone = EduCoreTone.Success)
                }
            }
        }

        if (state.canManage) {
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCorePrimaryButton("Add hostel", onOpenCreateHostel, Modifier.weight(1f))
                    EduCoreSecondaryButton("Allocate student", { onOpenAllocation(null) }, Modifier.weight(1f))
                }
            }
        }

        item { Text("Boarding houses", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold) }
        if (state.hostels.isEmpty() && !state.isLoading) {
            item { EduCoreEmptyState("No hostels configured", "Create a hostel before adding rooms or allocating residents.") }
        } else {
            items(state.hostels, key = HostelDto::id) { hostel ->
                HostelCard(
                    hostel = hostel,
                    canManage = state.canManage,
                    onAddRoom = { onOpenCreateRoom(hostel.id) },
                    onAllocate = { onOpenAllocation(hostel.id) },
                )
            }
        }

        item { Text("Active residents", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold) }
        item { EduCoreSearchBar(state.query, onQuery, placeholder = "Search resident, hostel or room") }
        item { EduCoreSecondaryButton("Search residents", onSearch, Modifier.fillMaxWidth(), enabled = !state.isLoading) }

        if (state.allocations.isEmpty() && !state.isLoading) {
            item { EduCoreEmptyState("No active hostel allocations", if (state.query.isBlank()) "Allocated residents will appear here." else "Try another resident, hostel or room search.") }
        } else {
            items(state.allocations, key = HostelAllocationDto::id) { allocation ->
                HostelAllocationCard(allocation, state.canManage, state.isSaving, onVacate)
            }
        }

        if (state.hasMore) {
            item { EduCoreSecondaryButton(if (state.isLoadingMore) "Loading…" else "Load more residents", onLoadMore, Modifier.fillMaxWidth(), enabled = !state.isLoadingMore) }
        }
        if (state.errorMessage != null && workspace == null) {
            item { EduCoreSecondaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
        }
    }
}

@Composable
private fun HostelCard(
    hostel: HostelDto,
    canManage: Boolean,
    onAddRoom: () -> Unit,
    onAllocate: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(hostel.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        "${hostel.gender.replaceFirstChar(Char::uppercase)} boarding · Warden: ${hostel.warden ?: "Not assigned"}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge(
                    "${hostel.occupied}/${hostel.capacity}",
                    if (hostel.occupied >= hostel.capacity) EduCoreTone.Danger else EduCoreTone.Success,
                )
            }
            if (hostel.rooms.isEmpty()) {
                Text("No rooms configured", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            } else {
                hostel.rooms.chunked(2).forEach { rowRooms ->
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                        rowRooms.forEach { room -> RoomSummary(room, Modifier.weight(1f)) }
                        if (rowRooms.size == 1) androidx.compose.foundation.layout.Spacer(Modifier.weight(1f))
                    }
                }
            }
            if (canManage) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton("Add room", onAddRoom, Modifier.weight(1f))
                    EduCoreSecondaryButton("Allocate", onAllocate, Modifier.weight(1f), enabled = hostel.occupied < hostel.capacity)
                }
            }
        }
    }
}

@Composable
private fun RoomSummary(room: HostelRoomDto, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.SurfaceBlue50),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.padding(EduCoreSpacing.Sm)) {
            Text("Room ${room.roomNumber}", fontWeight = FontWeight.Medium)
            Text("${room.occupied}/${room.capacity} occupied", style = MaterialTheme.typography.bodySmall)
        }
    }
}

@Composable
private fun HostelAllocationCard(
    allocation: HostelAllocationDto,
    canManage: Boolean,
    busy: Boolean,
    onVacate: (Long) -> Unit,
) {
    var confirm by rememberSaveable(allocation.id) { mutableStateOf(false) }
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    Text(allocation.student, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text(
                        listOfNotNull(allocation.admissionNumber, allocation.hostel, allocation.room?.let { "Room $it" }).joinToString(" · "),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                EduCoreStatusBadge("Resident", EduCoreTone.Success)
            }
            allocation.allocatedAt?.let { Text("Allocated $it", style = MaterialTheme.typography.bodySmall) }
            if (canManage && !confirm) {
                EduCoreSecondaryButton("Vacate resident", { confirm = true }, Modifier.fillMaxWidth(), enabled = !busy)
            } else if (canManage) {
                Text("End this student's active hostel allocation?", color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.SemiBold)
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    EduCoreSecondaryButton("Cancel", { confirm = false }, Modifier.weight(1f), enabled = !busy)
                    EduCoreDangerButton("Confirm vacate", { onVacate(allocation.id) }, Modifier.weight(1f), enabled = !busy)
                }
            }
        }
    }
}

@Composable
private fun CreateHostelScreen(
    state: HostelUiState,
    onBack: () -> Unit,
    onName: (String) -> Unit,
    onGender: (String) -> Unit,
    onCapacity: (String) -> Unit,
    onWarden: (Long?) -> Unit,
    onSave: () -> Unit,
) {
    val workspace = state.workspace ?: return
    var wardenQuery by rememberSaveable { mutableStateOf("") }
    val wardens = remember(workspace.wardens, wardenQuery) {
        workspace.wardens.filter { it.name.contains(wardenQuery, true) || it.staffId.orEmpty().contains(wardenQuery, true) }.take(50)
    }
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Add Hostel", "Create a boarding house and assign its capacity", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { EduCoreTextField(state.hostelName, onName, "Hostel name", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item {
            EduCoreSegmentedControl(
                listOf("Male", "Female", "Mixed"),
                when (state.hostelGender) { "male" -> 0; "female" -> 1; else -> 2 },
                { onGender(listOf("male", "female", "mixed")[it]) },
                enabled = !state.isSaving,
            )
        }
        item { EduCoreTextField(state.hostelCapacity, onCapacity, "Total capacity", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { Text("Warden (optional)", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
        item { EduCoreSecondaryButton("No warden", { onWarden(null) }, Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { EduCoreSearchBar(wardenQuery, { wardenQuery = it }, placeholder = "Search active staff") }
        items(wardens, key = HostelWardenDto::id) { warden ->
            SelectableHostelCard(warden.name, warden.staffId.orEmpty(), state.wardenId == warden.id) { onWarden(warden.id) }
        }
        item {
            EduCorePrimaryButton("Create hostel", onSave, Modifier.fillMaxWidth(), enabled = state.validHostelDraft, loading = state.isSaving)
        }
    }
}

@Composable
private fun CreateRoomScreen(
    state: HostelUiState,
    onBack: () -> Unit,
    onNumber: (String) -> Unit,
    onCapacity: (String) -> Unit,
    onSave: () -> Unit,
) {
    val hostel = state.hostels.firstOrNull { it.id == state.roomHostelId } ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Add Room", hostel.name, onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
        item { Text("Configured room capacity must remain within the hostel's total capacity of ${hostel.capacity}.", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant) }
        item { EduCoreTextField(state.roomNumber, onNumber, "Room number", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { EduCoreTextField(state.roomCapacity, onCapacity, "Room capacity", Modifier.fillMaxWidth(), enabled = !state.isSaving) }
        item { EduCorePrimaryButton("Add room", onSave, Modifier.fillMaxWidth(), enabled = state.validRoomDraft, loading = state.isSaving) }
    }
}

@Composable
private fun AllocateHostelScreen(
    state: HostelUiState,
    onBack: () -> Unit,
    onStudentQuery: (String) -> Unit,
    onSearchStudents: () -> Unit,
    onHostel: (Long?) -> Unit,
    onRoom: (Long?) -> Unit,
    onStudent: (Long?) -> Unit,
    onSave: () -> Unit,
) {
    val workspace = state.workspace ?: return
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item { EduCorePageHeader("Allocate Resident", "Select hostel, available room and unallocated student", onBack = onBack) }
        state.errorMessage?.let { item { EduCoreErrorBanner(it) } }

        item { Text("Hostel", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
        items(state.hostels.filter { it.occupied < it.capacity }, key = { "allocation-hostel-${it.id}" }) { hostel ->
            SelectableHostelCard(
                hostel.name,
                "${hostel.occupied}/${hostel.capacity} residents · ${hostel.gender.replaceFirstChar(Char::uppercase)}",
                state.allocationHostelId == hostel.id,
            ) { onHostel(hostel.id) }
        }

        state.selectedHostel?.let { hostel ->
            item { Text("Room", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
            if (state.availableRooms.isEmpty()) {
                item { EduCoreEmptyState("No available rooms", "Add a room or free capacity before allocating a resident.") }
            } else {
                items(state.availableRooms, key = { "allocation-room-${it.id}" }) { room ->
                    SelectableHostelCard(
                        "Room ${room.roomNumber}",
                        "${room.occupied}/${room.capacity} occupied",
                        state.allocationRoomId == room.id,
                    ) { onRoom(room.id) }
                }
            }
            if (hostel.rooms.isEmpty()) {
                item { Text("This hostel has no rooms configured yet.", color = MaterialTheme.colorScheme.error) }
            }
        }

        item { Text("Student", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold) }
        item { EduCoreSearchBar(state.studentQuery, onStudentQuery, placeholder = "Search unallocated student") }
        item { EduCoreSecondaryButton("Search students", onSearchStudents, Modifier.fillMaxWidth(), enabled = !state.isLoading) }
        if (workspace.unallocatedStudents.isEmpty() && !state.isLoading) {
            item { EduCoreEmptyState("No unallocated students", "All matching active students may already have hostel allocations.") }
        } else {
            items(workspace.unallocatedStudents, key = HostelStudentDto::id) { student ->
                SelectableHostelCard(
                    student.name,
                    listOfNotNull(student.admissionNumber, student.className).joinToString(" · "),
                    state.allocationStudentId == student.id,
                ) { onStudent(student.id) }
            }
        }

        item {
            EduCorePrimaryButton("Allocate student", onSave, Modifier.fillMaxWidth(), enabled = state.validAllocationDraft, loading = state.isSaving)
        }
    }
}

@Composable
private fun SelectableHostelCard(
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
        Row(Modifier.fillMaxWidth().padding(EduCoreSpacing.Md), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(title, fontWeight = FontWeight.Medium)
                if (subtitle.isNotBlank()) Text(subtitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            if (selected) EduCoreStatusBadge("Selected", EduCoreTone.Info)
        }
    }
}
