package online.educoreng.educore.presentation

import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import online.educoreng.educore.core.designsystem.component.EduCoreEmptyState
import online.educoreng.educore.core.designsystem.component.EduCoreErrorBanner
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
    onRetry: () -> Unit,
    onLogout: (() -> Unit)? = null,
) {
    Column(Modifier.fillMaxSize()) {
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
                    subtitle = "Monitor schools, subscriptions, payments, support and platform operations without leaving the native app.",
                )
            }
            item {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                ) {
                    PlatformSection.entries.forEach { section ->
                        FilterChip(
                            selected = state.section == section,
                            onClick = { onSection(section) },
                            label = { Text(section.label()) },
                        )
                    }
                }
            }
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading platform data") }

            when (state.section) {
                PlatformSection.OVERVIEW -> state.dashboard?.let { dashboard ->
                    item { EduCoreSectionHeader("Network overview", "Live platform-wide totals") }
                    item { PlatformMetricCard("Schools", dashboard.metrics.schools.toString(), "${dashboard.metrics.activeSchools} active") }
                    item { PlatformMetricCard("Students", dashboard.metrics.students.toString(), "Across all schools") }
                    item { PlatformMetricCard("Platform users", dashboard.metrics.platformUsers.toString(), "Tenant-linked accounts") }
                    item { PlatformMetricCard("Revenue this month", money(dashboard.metrics.monthlyRevenue), "Confirmed payments") }
                    item { PlatformMetricCard("Total revenue", money(dashboard.metrics.totalRevenue), "All confirmed platform payments") }
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
                            label = { Text("Search school or slug") },
                            singleLine = true,
                        )
                    }
                    item { EduCorePrimaryButton("Search schools", onSearch, Modifier.fillMaxWidth()) }
                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                            horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm),
                        ) {
                            listOf(null, "active", "pending", "suspended", "subscription_expired").forEach { status ->
                                FilterChip(
                                    selected = state.status == status,
                                    onClick = { onStatus(status) },
                                    label = { Text(status?.replace('_', ' ') ?: "All") },
                                )
                            }
                        }
                    }
                    val schools = state.tenants?.tenants.orEmpty()
                    if (!state.isLoading && schools.isEmpty()) item { EduCoreEmptyState("No schools found", "Adjust the search or status filter.") }
                    items(schools, key = { it.id }) { PlatformTenantCard(it) }
                }

                PlatformSection.BILLING -> state.billing?.let { billing ->
                    item { PlatformMetricCard("Confirmed revenue", money(billing.summary.confirmed), "All confirmed platform payments") }
                    item { PlatformMetricCard("Pending", money(billing.summary.pending), "Awaiting confirmation") }
                    item { PlatformMetricCard("This month", money(billing.summary.thisMonth), "Confirmed this month") }
                    item { EduCoreSectionHeader("Recent payments") }
                    if (billing.payments.isEmpty()) item { EduCoreEmptyState("No payments", "Platform payments will appear here when recorded.") }
                    items(billing.payments, key = { it.id }) { payment ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(payment.school, fontWeight = FontWeight.Bold)
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
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(plan.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                Text("${money(plan.rate)} · ${plan.cycle}")
                                plan.features.forEach { Text("• $it", style = MaterialTheme.typography.bodySmall) }
                            }
                        }
                    }
                }

                PlatformSection.AGENTS -> state.agents?.let { agents ->
                    item { EduCoreSectionHeader("Platform agents", "Referral network performance") }
                    if (agents.agents.isEmpty()) item { EduCoreEmptyState("No agents", "Registered platform agents will appear here.") }
                    items(agents.agents, key = { it.id }) { agent ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(agent.name, fontWeight = FontWeight.Bold)
                                Text(agent.email)
                                Text("${agent.referrals} referrals · ${agent.commissionRate}% commission")
                                Text("Earned ${money(agent.earned)} · Paid ${money(agent.paid)}")
                                EduCoreStatusBadge(if (agent.active) "Active" else "Inactive", if (agent.active) EduCoreTone.Success else EduCoreTone.Warning)
                            }
                        }
                    }
                }

                PlatformSection.ANALYTICS -> state.analytics?.let { analytics ->
                    item { EduCoreSectionHeader("Platform analytics", "Growth and pricing distribution") }
                    item { PlatformMetricCard("Schools", analytics.metrics.schools.toString(), "${analytics.metrics.activeSubscriptions} active subscriptions") }
                    item { PlatformMetricCard("Students", analytics.metrics.students.toString(), "Active enrollment across the network") }
                    item { PlatformMetricCard("Largest pricing segment", analytics.metrics.topTier, "By number of schools") }
                    item { EduCoreSectionHeader("School growth", "New tenants created this year") }
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
                    item { EduCoreSectionHeader("School groups", "Chains and shared-subscription campuses") }
                    if (groups.groups.isEmpty()) item { EduCoreEmptyState("No school groups", "School groups created on the platform will appear here.") }
                    items(groups.groups, key = { it.id }) { group ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(group.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                Text("${group.memberCount} member school(s)")
                                group.ownerName?.let { Text("Owner: $it", style = MaterialTheme.typography.bodySmall) }
                                group.ownerEmail?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                                group.description?.takeIf(String::isNotBlank)?.let { Text(it, style = MaterialTheme.typography.bodySmall) }
                            }
                        }
                    }
                }

                PlatformSection.SUPPORT -> state.support?.let { support ->
                    item { EduCoreSectionHeader("Support inbox", "School requests requiring platform attention") }
                    item {
                        PlatformMetricCard(
                            "Tickets",
                            (support.summary.open + support.summary.replied + support.summary.closed).toString(),
                            "${support.summary.open} open · ${support.summary.replied} replied · ${support.summary.closed} closed",
                        )
                    }
                    if (support.tickets.isEmpty()) item { EduCoreEmptyState("No support tickets", "Incoming school support requests will appear here.") }
                    items(support.tickets, key = { it.id }) { ticket ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(ticket.subject, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                                    EduCoreStatusBadge(ticket.status, ticket.status.supportTone())
                                }
                                Text(listOfNotNull(ticket.school, ticket.requester, ticket.createdAt).joinToString(" · "), style = MaterialTheme.typography.bodySmall)
                                Text(ticket.body, style = MaterialTheme.typography.bodyMedium)
                                ticket.adminReply?.takeIf(String::isNotBlank)?.let {
                                    Text("Platform reply: $it", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                                }
                            }
                        }
                    }
                }

                PlatformSection.BROADCASTS -> state.broadcasts?.let { broadcasts ->
                    item { EduCoreSectionHeader("Platform broadcasts", "Network-wide notices and targeted announcements") }
                    if (broadcasts.broadcasts.isEmpty()) item { EduCoreEmptyState("No broadcasts", "Platform broadcasts will appear here after creation.") }
                    items(broadcasts.broadcasts, key = { it.id }) { broadcast ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(broadcast.title, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
                                    EduCoreStatusBadge(if (broadcast.active) "Active" else "Expired", if (broadcast.active) EduCoreTone.Success else EduCoreTone.Neutral)
                                }
                                Text("Target: ${broadcast.target.replace('_', ' ')}", style = MaterialTheme.typography.bodySmall)
                                Text(broadcast.body)
                                Text(listOfNotNull(broadcast.creator, broadcast.createdAt, broadcast.expiresAt?.let { "Expires $it" }).joinToString(" · "), style = MaterialTheme.typography.bodySmall)
                            }
                        }
                    }
                }

                PlatformSection.SETTINGS -> state.settings?.let { settings ->
                    item { EduCoreSectionHeader("Platform settings", "Operational settings; encrypted secrets are intentionally excluded") }
                    if (settings.settings.isEmpty()) item { EduCoreEmptyState("No platform settings", "Operational settings will appear here after configuration.") }
                    items(settings.settings, key = { it.key }) { setting ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Text(setting.label, fontWeight = FontWeight.Bold)
                                Text(setting.value?.toString() ?: "Not configured")
                                Text("${setting.group} · ${setting.type}", style = MaterialTheme.typography.bodySmall, color = EduCoreColors.Slate600)
                            }
                        }
                    }
                }

                PlatformSection.GATEWAYS -> state.gateways?.let { gateways ->
                    item { EduCoreSectionHeader("Payment gateways", "Credential status only; secret keys never leave the server") }
                    items(gateways.gateways, key = { it.provider }) { gateway ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Xs)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(gateway.provider.replaceFirstChar(Char::uppercase), fontWeight = FontWeight.Bold)
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
                    )
                }
            }
        }
    }
}

@Composable
private fun PlatformTenantCard(tenant: PlatformTenantDto) {
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg), verticalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(tenant.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, modifier = Modifier.weight(1f))
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
    Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
        Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg)) {
            Text(label, style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Slate600)
            Text(value, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
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
    "replied" -> EduCoreTone.Info
    else -> EduCoreTone.Warning
}

private fun monthLabel(month: Int): String = listOf(
    "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
).getOrElse(month - 1) { "Month $month" }

private fun money(value: Double): String = "NGN ${String.format("%,.2f", value)}"
