package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Folder
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.HealthAndSafety
import androidx.compose.material.icons.filled.HomeWork
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.LibraryBooks
import androidx.compose.material.icons.filled.MenuBook
import androidx.compose.material.icons.filled.Message
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.People
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Subject
import androidx.compose.material.icons.filled.TaskAlt
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreModuleCard
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreQuickAction
import online.educoreng.educore.core.designsystem.component.EduCoreSearchBar
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseStat
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.component.EduCoreWarningBanner
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.layout.eduCoreScreenPadding
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.ClassSummary
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SessionSnapshot

/**
 * My Classes screen aligned to the approved August showcase.
 * No fabricated metrics are introduced: summary values come from the authorised
 * class catalogue returned by the mobile API, and action shortcuts are shown only
 * when the corresponding module exists in the authenticated bootstrap contract.
 */
@Composable
internal fun ShowcaseClassesScreen(
    session: SessionSnapshot,
    state: ClassesUiState,
    width: EduCoreWindowWidth,
    onSearch: (String) -> Unit,
    onOpenClass: (Long) -> Unit,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onRetry: () -> Unit,
) {
    val catalogue = state.catalogue
    if (state.isLoadingClasses && catalogue == null) {
        online.educoreng.educore.core.designsystem.component.EduCoreLoadingState(
            Modifier.fillMaxSize(),
            "Loading your classes",
        )
        return
    }
    if (catalogue == null) {
        online.educoreng.educore.core.designsystem.component.EduCoreErrorState(
            state.errorMessage ?: "Your class workspace is unavailable.",
            Modifier.fillMaxSize(),
            onRetry = onRetry,
        )
        return
    }

    val classes = remember(catalogue.classes, state.classSearch) {
        catalogue.classes.filter { item ->
            state.classSearch.isBlank() || listOf(
                item.name,
                item.formTutorName.orEmpty(),
                item.subjects.joinToString(" ") { it.name },
            ).any { it.contains(state.classSearch, ignoreCase = true) }
        }
    }
    val allStudents = catalogue.classes.sumOf { it.studentCount }
    val subjectCount = catalogue.classes.flatMap { it.subjects }.distinctBy { it.id }.size
    val actionModules = listOf("attendance", "scores", "lesson-planner", "academic-repository")
        .mapNotNull { key -> session.modules.firstOrNull { it.key.equals(key, ignoreCase = true) } }

    LazyColumn(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
    ) {
        item {
            EduCoreShowcaseHero(
                eyebrow = "MY CLASSES",
                title = "Everything you need, in one place.",
                subtitle = "Manage the classes, students and academic work assigned to your account.",
            ) {
                Surface(
                    shape = MaterialTheme.shapes.medium,
                    color = EduCoreColors.Gold400,
                    contentColor = EduCoreColors.Navy900,
                ) {
                    Text(
                        text = "${catalogue.classes.size} assigned class${if (catalogue.classes.size == 1) "" else "es"}",
                        modifier = Modifier.padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm),
                        style = MaterialTheme.typography.labelLarge,
                        fontWeight = FontWeight.Bold,
                    )
                }
            }
        }

        if (catalogue.isFromCache) {
            item { EduCoreWarningBanner("Showing the most recent class list saved on this device.") }
        }

        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
            ) {
                EduCoreShowcaseStat("Classes", catalogue.classes.size.toString(), Icons.Default.School, modifier = Modifier.weight(1f))
                EduCoreShowcaseStat("Students", allStudents.toString(), Icons.Default.People, tone = EduCoreTone.Success, modifier = Modifier.weight(1f))
                EduCoreShowcaseStat("Subjects", subjectCount.toString(), Icons.Default.Subject, tone = EduCoreTone.Accent, modifier = Modifier.weight(1f))
            }
        }

        if (actionModules.isNotEmpty()) {
            item { EduCoreSectionHeader("Quick links", supportingText = "Only actions permitted for your account are shown") }
            item {
                val columns = if (width == EduCoreWindowWidth.Compact) 2 else actionModules.size.coerceAtMost(4)
                androidx.compose.foundation.layout.FlowRow(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                    maxItemsInEachRow = columns,
                ) {
                    actionModules.forEach { module ->
                        EduCoreQuickAction(
                            label = compactModuleTitle(module),
                            icon = showcaseModuleIcon(module.key),
                            onClick = { onModuleClick(module) },
                            modifier = Modifier.weight(1f),
                        )
                    }
                }
            }
        }

        item { EduCoreSectionHeader("Assigned classes", supportingText = "Open a class for students, attendance, scores and timetable") }
        item {
            EduCoreSearchBar(
                value = state.classSearch,
                onValueChange = onSearch,
                placeholder = "Search class, subject or form tutor",
            )
        }

        if (classes.isEmpty()) {
            item {
                EduCoreEmptyState(
                    title = if (state.classSearch.isBlank()) "No assigned classes" else "No classes found",
                    message = if (state.classSearch.isBlank()) {
                        "No class workspace is currently assigned to this account."
                    } else {
                        "Try another class, subject or teacher name."
                    },
                )
            }
        } else {
            items(classes, key = ClassSummary::id) { classSummary ->
                ShowcaseClassCard(classSummary) { onOpenClass(classSummary.id) }
            }
        }
        item { Spacer(Modifier.height(EduCoreSpacing.Lg)) }
    }
}

@Composable
private fun ShowcaseClassCard(classSummary: ClassSummary, onClick: () -> Unit) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Surface(
                    modifier = Modifier.size(46.dp),
                    shape = MaterialTheme.shapes.medium,
                    color = EduCoreColors.Navy900,
                    contentColor = Color.White,
                ) {
                    Box(contentAlignment = Alignment.Center) { Icon(Icons.Default.School, null) }
                }
                Spacer(Modifier.size(EduCoreSpacing.Md))
                Column(Modifier.weight(1f)) {
                    Text(classSummary.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                    Text(
                        classSummary.subjects.joinToString(" • ") { it.name }.ifBlank { "Class workspace" },
                        style = MaterialTheme.typography.bodySmall,
                        color = EduCoreColors.Slate600,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
                EduCoreStatusBadge("${classSummary.studentCount} students", EduCoreTone.Success)
            }

            val capabilityLabels = buildList {
                if (classSummary.capabilities.markAttendance) add("Attendance")
                if (classSummary.capabilities.enterScores) add("Scores")
                if (classSummary.capabilities.planLessons) add("Lesson plan")
                if (classSummary.capabilities.viewResults) add("Results")
            }
            if (capabilityLabels.isNotEmpty()) {
                Text(
                    capabilityLabels.joinToString("  •  "),
                    style = MaterialTheme.typography.labelMedium,
                    color = EduCoreColors.Navy700,
                )
            }
            classSummary.formTutorName?.takeIf(String::isNotBlank)?.let {
                Text("Form tutor: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Muted500)
            }
        }
    }
}

/**
 * Role-aware Modules Hub based on the approved August "More" screen.
 * Categories are presentation only. The source list is session.modules, therefore
 * an unauthorised module never becomes visible simply because it belongs to a hub category.
 */
@Composable
internal fun ShowcaseStaffMoreScreen(
    session: SessionSnapshot,
    width: EduCoreWindowWidth,
    onModuleClick: (ModuleDescriptor) -> Unit,
    onLogout: () -> Unit,
) {
    val modules = remember(session.modules) {
        session.modules
            .filterNot { it.key.equals("dashboard", ignoreCase = true) }
            .distinctBy { canonicalShowcaseKey(it.key) }
    }
    val groups = remember(modules) {
        listOf(
            "Academics" to modules.filter { showcaseCategory(it.key) == "Academics" },
            "Operations" to modules.filter { showcaseCategory(it.key) == "Operations" },
            "Communication" to modules.filter { showcaseCategory(it.key) == "Communication" },
            "Account" to modules.filter { showcaseCategory(it.key) == "Account" },
        ).filter { it.second.isNotEmpty() }
    }
    val columns = when (width) {
        EduCoreWindowWidth.Compact -> 3
        EduCoreWindowWidth.Medium -> 4
        EduCoreWindowWidth.Expanded -> 5
    }

    LazyVerticalGrid(
        columns = GridCells.Fixed(columns),
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50),
        contentPadding = PaddingValues(eduCoreScreenPadding()),
        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
        verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
    ) {
        item(key = "account", span = { GridItemSpan(maxLineSpan) }) {
            EduCoreShowcaseHero(
                eyebrow = "MODULES HUB",
                title = session.user.name,
                subtitle = "${session.user.roleLabel} · ${session.school.name}",
            ) {
                Surface(
                    shape = MaterialTheme.shapes.medium,
                    color = Color.White.copy(alpha = 0.10f),
                    contentColor = Color.White,
                ) {
                    Text(
                        "${modules.size} authorised tools",
                        Modifier.padding(horizontal = EduCoreSpacing.Md, vertical = EduCoreSpacing.Sm),
                        style = MaterialTheme.typography.labelMedium,
                    )
                }
            }
        }

        if (groups.isEmpty()) {
            item(span = { GridItemSpan(maxLineSpan) }) {
                EduCoreEmptyState("No additional modules", "Your account has no additional module permissions.")
            }
        }

        groups.forEach { (title, groupModules) ->
            item(key = "heading-$title", span = { GridItemSpan(maxLineSpan) }) {
                EduCoreSectionHeader(title, supportingText = categorySupportingText(title))
            }
            items(groupModules, key = ModuleDescriptor::key) { module ->
                EduCoreModuleCard(
                    title = compactModuleTitle(module),
                    icon = showcaseModuleIcon(module.key),
                    onClick = { onModuleClick(module) },
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        }

        item(key = "sign-out", span = { GridItemSpan(maxLineSpan) }) {
            Column(verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                EduCorePrimaryButton(
                    text = "Sign out",
                    onClick = onLogout,
                    modifier = Modifier.fillMaxWidth(),
                    leadingIcon = { Icon(Icons.AutoMirrored.Filled.Logout, null) },
                )
                Text(
                    "Modules are controlled by your EduCore role and explicit permissions.",
                    modifier = Modifier.fillMaxWidth(),
                    style = MaterialTheme.typography.bodySmall,
                    color = EduCoreColors.Muted500,
                )
            }
        }
    }
}

private fun compactModuleTitle(module: ModuleDescriptor): String = when (module.key.lowercase()) {
    "attendance" -> "Attendance"
    "staff-attendance", "staff-attendance.self" -> "My Attendance"
    "academic-repository" -> "Repository"
    "lesson-planner" -> "Lesson Planner"
    "notifications.view", "announcements" -> "Notices"
    "calendar.view" -> "Events"
    "reports" -> "Results"
    "cbt" -> "Examinations"
    else -> module.title
}

private fun canonicalShowcaseKey(key: String): String = when (key.lowercase()) {
    "staff-attendance", "staff-attendance.self" -> "staff-attendance"
    "notifications.view", "announcements" -> "notices"
    else -> key.lowercase()
}

private fun showcaseCategory(key: String): String = when (key.lowercase()) {
    "students", "classes", "subjects", "curriculum", "attendance", "scores", "scores.entry",
    "reports", "report-cards", "results", "lesson-planner", "academic-repository", "library", "cbt",
    "academic-cycle" -> "Academics"

    "staff", "staff-attendance", "staff-attendance.self", "fees", "expenses", "payroll", "admissions",
    "transport", "health", "inventory", "hostels", "analytics", "exports" -> "Operations"

    "messages", "notifications.view", "announcements", "calendar.view" -> "Communication"
    "profile", "settings" -> "Account"
    else -> "Operations"
}

private fun categorySupportingText(category: String): String = when (category) {
    "Academics" -> "Teaching, learning and assessment"
    "Operations" -> "School administration and services"
    "Communication" -> "Messages, notices and events"
    else -> "Profile and account tools"
}

private fun showcaseModuleIcon(key: String): ImageVector = when (key.lowercase()) {
    "students" -> Icons.Default.People
    "staff" -> Icons.Default.Badge
    "classes" -> Icons.Default.School
    "subjects", "curriculum" -> Icons.Default.Subject
    "attendance", "staff-attendance", "staff-attendance.self" -> Icons.Default.TaskAlt
    "scores", "scores.entry", "reports", "report-cards", "results" -> Icons.Default.Assessment
    "lesson-planner" -> Icons.Default.MenuBook
    "academic-repository" -> Icons.Default.Folder
    "library" -> Icons.Default.LibraryBooks
    "cbt" -> Icons.Default.WorkspacePremium
    "fees", "expenses", "payroll" -> Icons.Default.AttachMoney
    "transport" -> Icons.Default.HomeWork
    "hostels" -> Icons.Default.HomeWork
    "inventory" -> Icons.Default.Inventory2
    "health" -> Icons.Default.HealthAndSafety
    "messages" -> Icons.Default.Message
    "notifications.view", "announcements" -> Icons.Default.Notifications
    "calendar.view" -> Icons.Default.CalendarMonth
    "profile" -> Icons.Default.Person
    "settings" -> Icons.Default.Settings
    else -> Icons.Default.CheckCircle
}
