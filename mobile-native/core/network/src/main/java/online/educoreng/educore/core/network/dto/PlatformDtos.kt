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
