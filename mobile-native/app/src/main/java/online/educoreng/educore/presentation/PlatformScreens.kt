package online.educoreng.educore.presentation

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
    onBack: () -> Unit,
    onSection: (PlatformSection) -> Unit,
    onSearchChange: (String) -> Unit,
    onSearch: () -> Unit,
    onStatus: (String?) -> Unit,
    onRetry: () -> Unit,
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
                    subtitle = "Monitor schools, subscriptions, revenue and platform growth without leaving the native app.",
                )
            }
            item {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
                    PlatformSection.entries.forEach { section ->
                        FilterChip(
                            selected = state.section == section,
                            onClick = { onSection(section) },
                            label = { Text(section.name.lowercase().replaceFirstChar(Char::uppercase)) },
                        )
                    }
                }
            }
            state.errorMessage?.let { item { EduCoreErrorBanner(it) } }
            if (state.isLoading) item { EduCoreLoadingState(message = "Loading platform data") }

            when (state.section) {
                PlatformSection.OVERVIEW -> state.dashboard?.let { dashboard ->
                    item { EduCoreSectionHeader("Network overview", "Live platform-wide totals") }
                    item {
                        PlatformMetricCard("Schools", dashboard.metrics.schools.toString(), "${dashboard.metrics.activeSchools} active")
                    }
                    item { PlatformMetricCard("Students", dashboard.metrics.students.toString(), "Across all schools") }
                    item { PlatformMetricCard("Platform users", dashboard.metrics.platformUsers.toString(), "Tenant-linked accounts") }
                    item { PlatformMetricCard("Revenue this month", money(dashboard.metrics.monthlyRevenue), "Confirmed payments") }
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
                        Row(horizontalArrangement = Arrangement.spacedBy(EduCoreSpacing.Sm)) {
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
                    items(billing.payments, key = { it.id }) { payment ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg)) {
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
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg)) {
                                Text(plan.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                Text("${money(plan.rate)} · ${plan.cycle}")
                                plan.features.forEach { Text("• $it", style = MaterialTheme.typography.bodySmall) }
                            }
                        }
                    }
                }
                PlatformSection.AGENTS -> state.agents?.let { agents ->
                    item { EduCoreSectionHeader("Platform agents", "Referral network performance") }
                    items(agents.agents, key = { it.id }) { agent ->
                        Card(Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = EduCoreColors.White)) {
                            Column(Modifier.fillMaxWidth().padding(EduCoreSpacing.Lg)) {
                                Text(agent.name, fontWeight = FontWeight.Bold)
                                Text(agent.email)
                                Text("${agent.referrals} referrals · ${agent.commissionRate}% commission")
                                Text("Earned ${money(agent.earned)} · Paid ${money(agent.paid)}")
                                EduCoreStatusBadge(if (agent.active) "Active" else "Inactive", if (agent.active) EduCoreTone.Success else EduCoreTone.Warning)
                            }
                        }
                    }
                }
            }

            if (!state.isLoading && state.errorMessage != null) {
                item { EduCorePrimaryButton("Retry", onRetry, Modifier.fillMaxWidth()) }
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

private fun money(value: Double): String = "NGN ${String.format("%,.2f", value)}"
