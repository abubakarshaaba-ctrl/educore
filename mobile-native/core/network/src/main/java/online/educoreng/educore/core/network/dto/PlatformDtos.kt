package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PlatformOperatorDto(val name: String, val role: String)

data class PlatformMetricsDto(
    val schools: Int = 0,
    @param:Json(name = "active_schools") val activeSchools: Int = 0,
    val students: Int = 0,
    @param:Json(name = "platform_users") val platformUsers: Int = 0,
    @param:Json(name = "monthly_revenue") val monthlyRevenue: Double = 0.0,
    @param:Json(name = "total_revenue") val totalRevenue: Double = 0.0,
)

data class PlatformAttentionDto(
    val pending: Int = 0,
    val suspended: Int = 0,
    val expired: Int = 0,
    @param:Json(name = "expiring_soon") val expiringSoon: Int = 0,
)

data class PlatformTenantDto(
    val id: Long,
    val name: String,
    val slug: String,
    val email: String? = null,
    val phone: String? = null,
    val address: String? = null,
    val status: String,
    val plan: String? = null,
    @param:Json(name = "students_capacity") val studentsCapacity: Int? = null,
    @param:Json(name = "subscription_expires_at") val subscriptionExpiresAt: String? = null,
    val users: Int = 0,
    val students: Int = 0,
)

data class PlatformDashboardDto(
    val operator: PlatformOperatorDto,
    val metrics: PlatformMetricsDto = PlatformMetricsDto(),
    val attention: PlatformAttentionDto = PlatformAttentionDto(),
    @param:Json(name = "recent_schools") val recentSchools: List<PlatformTenantDto> = emptyList(),
)

data class PlatformTenantsDto(val tenants: List<PlatformTenantDto> = emptyList())

data class PlatformBillingSummaryDto(
    val confirmed: Double = 0.0,
    val pending: Double = 0.0,
    @param:Json(name = "this_month") val thisMonth: Double = 0.0,
)

data class PlatformPaymentDto(
    val id: Long,
    val reference: String? = null,
    val school: String,
    val amount: Double = 0.0,
    val currency: String? = null,
    val status: String,
    val method: String? = null,
    @param:Json(name = "paid_at") val paidAt: String? = null,
)

data class PlatformBillingDto(
    val summary: PlatformBillingSummaryDto = PlatformBillingSummaryDto(),
    val payments: List<PlatformPaymentDto> = emptyList(),
)

data class PlatformPlanDto(
    val id: Long,
    val name: String,
    val rate: Double = 0.0,
    val cycle: String,
    val active: Boolean = true,
    val features: List<String> = emptyList(),
)

data class PlatformPlansDto(
    val model: String,
    @param:Json(name = "annual_discount_percent") val annualDiscountPercent: Double = 0.0,
    val plans: List<PlatformPlanDto> = emptyList(),
)

data class PlatformAgentDto(
    val id: Long,
    val name: String,
    val email: String,
    val phone: String? = null,
    val state: String? = null,
    @param:Json(name = "commission_rate") val commissionRate: Double = 0.0,
    @param:Json(name = "referral_code") val referralCode: String,
    val active: Boolean = true,
    val referrals: Int = 0,
    val earned: Double = 0.0,
    val paid: Double = 0.0,
)

data class PlatformAgentsDto(val agents: List<PlatformAgentDto> = emptyList())

data class PlatformAnalyticsMetricsDto(
    val schools: Int = 0,
    @param:Json(name = "active_subscriptions") val activeSubscriptions: Int = 0,
    val students: Int = 0,
    @param:Json(name = "top_tier") val topTier: String = "None",
)

data class PlatformGrowthDto(val month: Int, val year: Int, val count: Int)
data class PlatformPlanDistributionDto(val plan: String, val count: Int)
data class PlatformAnalyticsDto(
    val metrics: PlatformAnalyticsMetricsDto = PlatformAnalyticsMetricsDto(),
    val growth: List<PlatformGrowthDto> = emptyList(),
    @param:Json(name = "plan_distribution") val planDistribution: List<PlatformPlanDistributionDto> = emptyList(),
)

data class PlatformGroupDto(
    val id: Long,
    val name: String,
    val slug: String? = null,
    val description: String? = null,
    @param:Json(name = "owner_name") val ownerName: String? = null,
    @param:Json(name = "owner_email") val ownerEmail: String? = null,
    @param:Json(name = "member_count") val memberCount: Int = 0,
    @param:Json(name = "created_at") val createdAt: String? = null,
)
data class PlatformGroupsDto(val groups: List<PlatformGroupDto> = emptyList())

data class PlatformGroupMemberDto(
    @param:Json(name = "tenant_id") val tenantId: Long,
    val name: String,
    val slug: String,
    val status: String,
    val role: String,
    @param:Json(name = "subscription_expires_at") val subscriptionExpiresAt: String? = null,
)

data class PlatformAvailableSchoolDto(
    val id: Long,
    val name: String,
    val slug: String,
    val status: String,
)

data class PlatformGroupDetailDto(
    val group: PlatformGroupDto,
    val members: List<PlatformGroupMemberDto> = emptyList(),
    @param:Json(name = "available_schools") val availableSchools: List<PlatformAvailableSchoolDto> = emptyList(),
)

data class PlatformGroupCreateRequestDto(
    val name: String,
    val description: String? = null,
    @param:Json(name = "owner_name") val ownerName: String? = null,
    @param:Json(name = "owner_email") val ownerEmail: String? = null,
)

data class PlatformGroupMemberRequestDto(
    @param:Json(name = "tenant_id") val tenantId: Long,
    val role: String = "member",
)

data class PlatformSupportSummaryDto(
    val open: Int = 0,
    val replied: Int = 0,
    val closed: Int = 0,
)
data class PlatformSupportTicketDto(
    val id: Long,
    @param:Json(name = "tenant_id") val tenantId: Long,
    val school: String? = null,
    val requester: String? = null,
    val subject: String,
    val body: String,
    val status: String,
    @param:Json(name = "admin_reply") val adminReply: String? = null,
    @param:Json(name = "replied_at") val repliedAt: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
)
data class PlatformSupportDto(
    val summary: PlatformSupportSummaryDto = PlatformSupportSummaryDto(),
    val tickets: List<PlatformSupportTicketDto> = emptyList(),
)

data class PlatformBroadcastDto(
    val id: Long,
    val title: String,
    val body: String,
    val target: String,
    val creator: String? = null,
    @param:Json(name = "expires_at") val expiresAt: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
    val active: Boolean = true,
)
data class PlatformBroadcastsDto(val broadcasts: List<PlatformBroadcastDto> = emptyList())

data class PlatformSettingDto(
    val key: String,
    val label: String,
    val group: String,
    val type: String,
    val value: Any? = null,
)
data class PlatformSettingsDto(val settings: List<PlatformSettingDto> = emptyList())

data class PlatformGatewayDto(
    val provider: String,
    @param:Json(name = "public_identifier") val publicIdentifier: String? = null,
    @param:Json(name = "contract_code") val contractCode: String? = null,
    @param:Json(name = "secret_configured") val secretConfigured: Boolean = false,
    val live: Boolean = false,
    val configured: Boolean = false,
)
data class PlatformGatewaysDto(val gateways: List<PlatformGatewayDto> = emptyList())

data class PlatformAdminDto(
    val id: Long,
    val name: String,
    val email: String? = null,
    val active: Boolean = true,
)

data class PlatformSubscriptionDto(
    @param:Json(name = "is_free") val isFree: Boolean = false,
    @param:Json(name = "expires_at") val expiresAt: String? = null,
    @param:Json(name = "can_extend") val canExtend: Boolean = false,
    @param:Json(name = "allowed_months") val allowedMonths: List<Int> = emptyList(),
)

data class PlatformTenantDetailDto(
    val tenant: PlatformTenantDto,
    val admins: List<PlatformAdminDto> = emptyList(),
    val subscription: PlatformSubscriptionDto = PlatformSubscriptionDto(),
)

data class PlatformTenantUpdateRequestDto(
    val status: String? = null,
    val reason: String? = null,
)

data class PlatformTenantExtendRequestDto(
    val months: Int,
    val reason: String,
)

data class PlatformTenantMutationResponseDto(
    val message: String,
    val tenant: PlatformTenantDto,
)

data class PlatformSettingsUpdateRequestDto(
    val settings: Map<String, Any?>,
    val reason: String,
)

data class PlatformGatewayUpdateRequestDto(
    @param:Json(name = "public_key") val publicKey: String,
    @param:Json(name = "secret_key") val secretKey: String? = null,
    @param:Json(name = "contract_code") val contractCode: String? = null,
    val live: Boolean,
    val reason: String,
)

data class PlatformSettingsMutationResponseDto(
    val message: String,
    @param:Json(name = "changed_keys") val changedKeys: List<String> = emptyList(),
    val provider: String? = null,
    @param:Json(name = "secret_replaced") val secretReplaced: Boolean? = null,
    val live: Boolean? = null,
)

data class PlatformSupportReplyRequestDto(val reply: String)

data class PlatformBroadcastCreateRequestDto(
    val title: String,
    val body: String,
    val target: String,
    @param:Json(name = "expires_at") val expiresAt: String? = null,
)

data class PlatformMutationResponseDto(
    val message: String,
    val status: String? = null,
    val id: Long? = null,
)
