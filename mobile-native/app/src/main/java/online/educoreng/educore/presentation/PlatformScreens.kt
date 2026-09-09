package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import online.educoreng.educore.core.designsystem.component.EduCoreConfirmationDialog
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreLoadingState
import online.educoreng.educore.core.designsystem.component.EduCorePageHeader
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSectionHeader
import online.educoreng.educore.core.designsystem.component.EduCoreShowcaseHero
import online.educoreng.educore.core.designsystem.component.EduCoreStatusBadge
import online.educoreng.educore.core.designsystem.component.EduCoreTone
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.network.dto.PlatformTenantDto

@Composable
internal fun PlatformScreen(
    state: PlatformUiState,
    onBack: (() -> Unit)? = null,
    onSection: (PlatformSection) -> Unit,
    onSearchChange: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String?) -> Unit,
    onEditReply: (Long, String?) -> Unit,
    onReplyDraft: (String) -> Unit,
    onSendReply: () -> Unit,
    onCancelReply: () -> Unit,
    onRequestCloseTicket: (Long) -> Unit,
    onOpenBroadcastEditor: () -> Unit,
    onCloseBroadcastEditor: () -> Unit,
    onBroadcastTitle: (String) -> Unit,
    onBroadcastBody: (String) -> Unit,
    onBroadcastTarget: (String) -> Unit,
    onBroadcastExpiresAt: (String) -> Unit,
    onCreateBroadcast: () -> Unit,
    onRequestExpireBroadcast: (Long) -> Unit,
    onConfirmPendingAction: () -> Unit,
    onDismissPendingAction: () -> Unit,
    onRetry: () -> Unit,
    onLogout: (() -> Unit)? = null,
) {
    EduCoreConfirmationDialog(
        visible = state.pendingAction != null,
        title = when (state.pendingAction) {
            PlatformPendingAction.CLOSE_SUPPORT -> "Close support ticket?"
            PlatformPendingAction.EXPIRE_BROADCAST -> "Expire platform broadcast?"
            null -> "Confirm platform action"
        },
        message = when (state.pendingAction) {
            PlatformPendingAction.CLOSE_SUPPORT -> "The ticket will be marked closed and no further platform reply can be added from the native workflow."
            PlatformPendingAction.EXPIRE_BROADCAST -> "The broadcast will stop appearing as an active platform notice immediately."
            null -> "Confirm this platform action."
        },
        confirmLabel = when (state.pendingAction) {
            PlatformPendingAction.CLOSE_SUPPORT -> "Close ticket"
            PlatformPendingAction.EXPIRE_BROADCAST -> "Expire"
            null -> "Confirm"
        },
        destructive = true,
        onConfirm = onConfirmPendingAction,
        onDismiss = onDismissPendingAction,
    )

    Column(Modifier.fillMaxSize().statusBarsPadding()) {
        EduCorePageHeader("Platform Administration", "EduCore network control centre", onBack = onBack)
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = androidx.compose.foundation.layout.PaddingValues(EduCoreSpacing.Lg),
            verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Md),
        ) {
            item {
                EduCoreShowcaseHero(
                    eyebrow = "PLATFORM",
                    title = state.dashboard?.operator?.name ?: "Super Admin",
                    subtitle = "Schools, subscriptions, payments and platform operations.",
                )
            }
            item {
                Row(
                    modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    PlatformSection.entries.forEach { section ->
                        PlatformFilterChip(
                            selected = state.section == section,
                            onClick = { onSection(section) },
                            enabled = !state.isMutating,
                            label = section.label(),
                        )
                    }
                }
            }
            state.message?.let { item { EduCoreInfoBanner(it, title = "Platform updated") } }
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading platform data") }

            when (state.section) {
                PlatformSection.OVERVIEW -> state.dashboard?.let { dashboard ->
                    item { EduCoreSectionHeader("Network overview") }
                    item { PlatformMetricCard("Schools", dashboard.metrics.schools.toString(), "${dashboard.metrics.activeSchools} active") }
                    item { PlatformMetricCard("Students", dashboard.metrics.students.toString(), "Across all schools") }
                    item { PlatformMetricCard("Platform users", dashboard.metrics.platformUsers.toString(), "Tenant-linked accounts") }
                    item { PlatformMetricCard("Revenue this month", money(dashboard.metrics.monthlyRevenue), "Confirmed payments") }
                    item { PlatformMetricCard("Total revenue", money(dashboard.metrics.totalRevenue), "Confirmed payments") }
                    item {
                        EduCoreSectionHeader(
                            "Attention",
                            "${dashboard.attention.pending} pending · ${dashboard.attention.suspended} suspended · ${dashboard.attention.expired} expired · ${dashboard.attention.expiringSoon} expiring soon",
                        )
                    }
                    items(dashboard.recentSchools, key = { "recent-${it.id}" }) { PlatformTenantCard(it) }
                }

                PlatformSection.SCHOOLS -> {
                    item {
                        OutlinedTextField(
                            value = state.search,
                            onValueChange = onSearchChange,
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !state.isMutating,
                            label = { Text("Search school or slug") },
                            singleLine = true,
                        )
                    }
                    item { EduCorePrimaryButton("Search schools", onSearch, Modifier.fillMaxWidth(), enabled = !state.isMutating) }
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                        ) {
                            listOf(null, "active", "pending", "suspended", "subscription_expired").forEach { status ->
                                PlatformFilterChip(
                                    selected = state.status == status,
                                    onClick = { onStatus(status) },
                                    enabled = !state.isMutating,
                                    label = status?.replace('_', ' ') ?: "All",
                                )
                            }
                        }
                    }
                    val schools = state.tenants?.tenants.orEmpty()
                    if (!state.isLoading && schools.isEmpty()) item { EduCoreEmptyState("No schools found", "Adjust the search or status filter.") }
                    items(schools, key = { it.id }) { PlatformTenantCard(it) }
                }

                PlatformSection.BILLING -> state.billing?.let { billing ->
                    item { PlatformMetricCard("Confirmed revenue", money(billing.summary.confirmed), "All confirmed payments") }
                    item { PlatformMetricCard("Pending", money(billing.summary.pending), "Awaiting confirmation") }
                    item { PlatformMetricCard("This month", money(billing.summary.thisMonth), "Confirmed this month") }
                    item { EduCoreSectionHeader("Recent payments") }
                    if (billing.payments.isEmpty()) item { EduCoreEmptyState("No payments", "Platform payments will appear here when recorded.") }
                    items(billing.payments, key = { it.id }) { payment ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(payment.school, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                Text(money(payment.amount) + (payment.currency?.let { " $it" } ?: ""))
                                Text(listOfNotNull(payment.reference, payment.method, payment.paidAt).joinToString(" · "), style = MaterialTheme.typography.bodySmall)
                                EduCoreStatusBadge(payment.status, if (payment.status == "confirmed") EduCoreTone.Success else EduCoreTone.Warning)
                            }
                        }
                    }
                }

                PlatformSection.PLANS -> state.plans?.let { plans ->
                    item { EduCoreSectionHeader("Pricing", plans.model) }
                    items(plans.plans, key = { it.id }) { plan ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(plan.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                Text("${money(plan.rate)} · ${plan.cycle}", color = EduCoreColors.Gold700)
                                plan.features.forEach { Text("• $it", style = MaterialTheme.typography.bodySmall) }
                            }
                        }
                    }
                }

                PlatformSection.AGENTS -> state.agents?.let { agents ->
                    item { EduCoreSectionHeader("Platform agents") }
                    if (agents.agents.isEmpty()) item { EduCoreEmptyState("No agents", "Registered platform agents will appear here.") }
                    items(agents.agents, key = { it.id }) { agent ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(agent.name, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                Text(agent.email)
                                Text("${agent.referrals} referrals · ${agent.commissionRate}% commission")
                                Text("Earned ${money(agent.earned)} · Paid ${money(agent.paid)}")
                                EduCoreStatusBadge(if (agent.active) "Active" else "Inactive", if (agent.active) EduCoreTone.Success else EduCoreTone.Warning)
                            }
                        }
                    }
                }

                PlatformSection.ANALYTICS -> state.analytics?.let { analytics ->
                    item { EduCoreSectionHeader("Platform analytics") }
                    item { PlatformMetricCard("Schools", analytics.metrics.schools.toString(), "${analytics.metrics.activeSubscriptions} active subscriptions") }
                    item { PlatformMetricCard("Students", analytics.metrics.students.toString(), "Active enrollment") }
                    item { PlatformMetricCard("Largest pricing segment", analytics.metrics.topTier, "By schools") }
                    item { EduCoreSectionHeader("School growth") }
                    if (analytics.growth.isEmpty()) item { EduCoreEmptyState("No growth records", "No school creation activity is recorded for the current year.") }
                    items(analytics.growth, key = { "${it.year}-${it.month}" }) { point ->
                        PlatformMetricCard(monthLabel(point.month), point.count.toString(), point.year.toString())
                    }
                    item { EduCoreSectionHeader("Pricing distribution") }
                    items(analytics.planDistribution, key = { it.plan }) { tier ->
                        PlatformMetricCard(tier.plan, tier.count.toString(), "schools")
                    }
                }

                PlatformSection.GROUPS -> state.groups?.let { groups ->
                    item { EduCoreSectionHeader("School groups") }
                    if (groups.groups.isEmpty()) item { EduCoreEmptyState("No school groups", "School groups created on the platform will appear here.") }
                    items(groups.groups, key = { it.id }) { group ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(group.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                Text("${group.memberCount} member school(s)")
                                group.ownerName?.let { Text("Owner: $it", style = MaterialTheme.typography.bodySmall) }
                                group.ownerEmail?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                                group.description?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                            }
                        }
                    }
                }

                PlatformSection.SUPPORT -> state.support?.let { support ->
                    item { EduCoreSectionHeader("Support inbox") }
                    item {
                        PlatformMetricCard(
                            "Tickets",
                            (support.summary.open + support.summary.replied + support.summary.closed).toString(),
                            "${support.summary.open} open · ${support.summary.replied} answered · ${support.summary.closed} closed",
                        )
                    }
                    if (support.tickets.isEmpty()) item { EduCoreEmptyState("No support tickets", "Incoming school support requests will appear here.") }
                    items(support.tickets, key = { it.id }) { ticket ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(ticket.subject, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900, modifier = Modifier.weight(1f))
                                    EduCoreStatusBadge(ticket.status, ticket.status.supportTone())
                                }
                                Text(listOfNotNull(ticket.school, ticket.requester, ticket.createdAt).joinToString(" · "), style = MaterialTheme.typography.bodySmall)
                                Text(ticket.body, style = MaterialTheme.typography.bodyMedium)
                                ticket.adminReply?.takeIf(String::isNotBlank)?.let {
                                    Text("Platform reply: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                                }
                                if (state.replyTicketId == ticket.id) {
                                    OutlinedTextField(
                                        value = state.replyDraft,
                                        onValueChange = onReplyDraft,
                                        modifier = Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                        label = { Text("Platform reply") },
                                        minLines = 3,
                                        maxLines = 8,
                                        supportingText = { Text("${state.replyDraft.length}/3000") },
                                    )
                                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                        EduCorePrimaryButton("Send reply", onSendReply, Modifier.weight(1f), enabled = state.replyValid, loading = state.isMutating)
                                        EduCoreSecondaryButton("Cancel", onCancelReply, Modifier.weight(1f), enabled = !state.isMutating)
                                    }
                                } else if (ticket.status != "closed") {
                                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                        EduCoreSecondaryButton("Reply", { onEditReply(ticket.id, ticket.adminReply) }, Modifier.weight(1f), enabled = !state.isMutating)
                                        EduCoreSecondaryButton("Close ticket", { onRequestCloseTicket(ticket.id) }, Modifier.weight(1f), enabled = !state.isMutating)
                                    }
                                }
                            }
                        }
                    }
                }

                PlatformSection.BROADCASTS -> state.broadcasts?.let { broadcasts ->
                    item { EduCoreSectionHeader("Platform broadcasts") }
                    item {
                        if (!state.broadcastEditorOpen) {
                            EduCorePrimaryButton("Create broadcast", onOpenBroadcastEditor, Modifier.fillMaxWidth(), enabled = !state.isMutating)
                        } else {
                            Card(
                                Modifier.fillMaxWidth(),
                                colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                                border = BorderStroke(1.dp, EduCoreColors.Line200),
                            ) {
                                Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                    Text("New platform broadcast", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                    OutlinedTextField(
                                        value = state.broadcastTitle,
                                        onValueChange = onBroadcastTitle,
                                        modifier = Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                        label = { Text("Title") },
                                        singleLine = true,
                                    )
                                    OutlinedTextField(
                                        value = state.broadcastBody,
                                        onValueChange = onBroadcastBody,
                                        modifier = Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                        label = { Text("Message") },
                                        minLines = 4,
                                        maxLines = 10,
                                    )
                                    Text("Audience", style = MaterialTheme.typography.labelLarge)
                                    Row(
                                        modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                                        horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                                    ) {
                                        listOf("all", "active", "trial", "expired").forEach { target ->
                                            PlatformFilterChip(
                                                selected = state.broadcastTarget == target,
                                                onClick = { onBroadcastTarget(target) },
                                                enabled = !state.isMutating,
                                                label = target.replaceFirstChar(Char::uppercase),
                                            )
                                        }
                                    }
                                    OutlinedTextField(
                                        value = state.broadcastExpiresAt,
                                        onValueChange = onBroadcastExpiresAt,
                                        modifier = Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                        label = { Text("Expires at (optional)") },
                                        supportingText = { Text("Future date/time, e.g. 2026-09-30 18:00:00") },
                                        singleLine = true,
                                    )
                                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                        EduCorePrimaryButton("Publish", onCreateBroadcast, Modifier.weight(1f), enabled = state.broadcastValid, loading = state.isMutating)
                                        EduCoreSecondaryButton("Cancel", onCloseBroadcastEditor, Modifier.weight(1f), enabled = !state.isMutating)
                                    }
                                }
                            }
                        }
                    }
                    if (broadcasts.broadcasts.isEmpty()) item { EduCoreEmptyState("No broadcasts", "Platform broadcasts will appear here after creation.") }
                    items(broadcasts.broadcasts, key = { it.id }) { broadcast ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(broadcast.title, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900, modifier = Modifier.weight(1f))
                                    EduCoreStatusBadge(if (broadcast.active) "Active" else "Expired", if (broadcast.active) EduCoreTone.Success else EduCoreTone.Neutral)
                                }
                                Text("Target: ${broadcast.target.replace('_', ' ')}", style = MaterialTheme.typography.bodySmall)
                                Text(broadcast.body)
                                Text(listOfNotNull(broadcast.creator, broadcast.createdAt, broadcast.expiresAt?.let { "Expires $it" }).joinToString(" · "), style = MaterialTheme.typography.bodySmall)
                                if (broadcast.active) {
                                    EduCoreSecondaryButton(
                                        "Expire broadcast",
                                        { onRequestExpireBroadcast(broadcast.id) },
                                        Modifier.fillMaxWidth(),
                                        enabled = !state.isMutating,
                                    )
                                }
                            }
                        }
                    }
                }

                PlatformSection.SETTINGS -> state.settings?.let { settings ->
                    item { EduCoreSectionHeader("Platform settings") }
                    if (settings.settings.isEmpty()) item { EduCoreEmptyState("No platform settings", "Operational settings will appear here after configuration.") }
                    items(settings.settings, key = { it.key }) { setting ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(setting.label, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                Text(setting.value?.toString() ?: "Not configured")
                                Text("${setting.group} · ${setting.type}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                            }
                        }
                    }
                }

                PlatformSection.GATEWAYS -> state.gateways?.let { gateways ->
                    item { EduCoreSectionHeader("Payment gateways") }
                    items(gateways.gateways, key = { it.provider }) { gateway ->
                        Card(
                            Modifier.fillMaxWidth(),
                            colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
                            border = BorderStroke(1.dp, EduCoreColors.Line200),
                        ) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(gateway.provider.replaceFirstChar(Char::uppercase), fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
                                    EduCoreStatusBadge(if (gateway.configured) "Configured" else "Incomplete", if (gateway.configured) EduCoreTone.Success else EduCoreTone.Warning)
                                }
                                Text(if (gateway.live) "LIVE mode" else "Test mode", style = MaterialTheme.typography.bodySmall)
                                gateway.publicIdentifier?.let { Text("Public identifier: $it", style = MaterialTheme.typography.bodySmall) }
                                gateway.contractCode?.let { Text("Contract: $it", style = MaterialTheme.typography.bodySmall) }
                                Text("Secret credential: ${if (gateway.secretConfigured) "configured" else "missing"}", style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                }
            }

            if (!state.isLoading && state.errorMessage != null) {
                item { EduCorePrimaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
            }
            onLogout?.let { logout ->
                item {
                    EduCoreSecondaryButton(
                        text = "Sign out of Platform",
                        onClick = logout,
                        modifier = Modifier.fillMaxWidth(),
                        enabled = !state.isMutating,
                    )
                }
            }
        }
    }
}

@Composable
private fun PlatformFilterChip(
    selected: Boolean,
    onClick: () -> Unit,
    enabled: Boolean,
    label: String,
) {
    FilterChip(
        selected = selected,
        onClick = onClick,
        enabled = enabled,
        label = {
            Text(
                text = label,
                color = if (selected) EduCoreColors.Gold700 else EduCoreColors.Navy900,
                fontWeight = if (selected) FontWeight.Medium else FontWeight.Normal,
            )
        },
        colors = FilterChipDefaults.filterChipColors(
            containerColor = EduCoreColors.White,
            selectedContainerColor = EduCoreColors.White,
            disabledContainerColor = EduCoreColors.Surface100,
            disabledSelectedContainerColor = EduCoreColors.Surface100,
        ),
        border = FilterChipDefaults.filterChipBorder(
            enabled = enabled,
            selected = selected,
            borderColor = EduCoreColors.Line300,
            selectedBorderColor = if (selected) EduCoreColors.Gold600 else EduCoreColors.Line300,
        ),
    )
}

@Composable
private fun PlatformTenantCard(tenant: PlatformTenantDto) {
    Card(
        Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(
                    tenant.name,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Medium,
                    color = EduCoreColors.Navy900,
                    modifier = Modifier.weight(1f),
                )
                EduCoreStatusBadge(tenant.status.replace('_', ' '), if (tenant.status == "active") EduCoreTone.Success else EduCoreTone.Warning)
            }
            Text(tenant.slug, style = MaterialTheme.typography.bodySmall)
            Text("${tenant.students} students · ${tenant.users} users · ${tenant.plan ?: "Plan unavailable"}")
            tenant.subscriptionExpiresAt?.let { Text("Subscription expires $it", style = MaterialTheme.typography.bodySmall) }
        }
    }
}

@Composable
private fun PlatformMetricCard(label: String, value: String, supporting: String) {
    Card(
        Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = EduCoreColors.White),
        border = BorderStroke(1.dp, EduCoreColors.Line200),
    ) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg)) {
            Text(label, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Slate600)
            Text(value, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Medium, color = EduCoreColors.Navy900)
            Text(supporting, style = MaterialTheme.typography.bodySmall)
        }
    }
}

private fun PlatformSection.label(): String = when (this) {
    PlatformSection.OVERVIEW -> "Overview"
    PlatformSection.SCHOOLS -> "Schools"
    PlatformSection.BILLING -> "Payments"
    PlatformSection.PLANS -> "Plans"
    PlatformSection.AGENTS -> "Agents"
    PlatformSection.ANALYTICS -> "Analytics"
    PlatformSection.GROUPS -> "Groups"
    PlatformSection.SUPPORT -> "Support"
    PlatformSection.BROADCASTS -> "Broadcasts"
    PlatformSection.SETTINGS -> "Settings"
    PlatformSection.GATEWAYS -> "Gateways"
}

private fun String.supportTone(): EduCoreTone = when (lowercase()) {
    "closed" -> EduCoreTone.Neutral
    "answered", "replied" -> EduCoreTone.Info
    else -> EduCoreTone.Warning
}

private fun monthLabel(month: Int): String = listOf(
    "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
).getOrElse(month - 1) { "Month $month" }

private fun money(value: Double): String = "NGN ${String.format("%,.2f", value)}"
