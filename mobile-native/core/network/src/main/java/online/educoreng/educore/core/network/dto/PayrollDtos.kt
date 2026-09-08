package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PayrollCapabilitiesDto(
    val manage: Boolean = false,
)

data class PayrollMetricsDto(
    val periods: Int = 0,
    val draft: Int = 0,
    val approved: Int = 0,
    val paid: Int = 0,
    @param:Json(name = "net_total") val netTotal: Double = 0.0,
)

data class PayrollOptionDto(
    val key: String,
    val label: String,
)

data class PayrollPeriodDto(
    val id: Long,
    val title: String,
    val start: String,
    val end: String,
    val status: String,
    val gross: Double = 0.0,
    val deductions: Double = 0.0,
    val net: Double = 0.0,
    @param:Json(name = "approved_by") val approvedBy: Long? = null,
    @param:Json(name = "payment_date") val paymentDate: String? = null,
)

data class PayrollSelectedDto(
    val search: String = "",
    val status: String = "all",
)

data class PayrollMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 30,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class PayrollWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: PayrollCapabilitiesDto = PayrollCapabilitiesDto(),
    val metrics: PayrollMetricsDto = PayrollMetricsDto(),
    @param:Json(name = "status_options") val statusOptions: List<PayrollOptionDto> = emptyList(),
    val periods: List<PayrollPeriodDto> = emptyList(),
    val selected: PayrollSelectedDto = PayrollSelectedDto(),
    val meta: PayrollMetaDto = PayrollMetaDto(),
)

data class PayrollDeductionLineDto(
    val label: String? = null,
    val amount: Double? = null,
)

data class PayrollItemDto(
    val id: Long,
    @param:Json(name = "staff_id") val staffId: Long,
    @param:Json(name = "staff_name") val staffName: String? = null,
    @param:Json(name = "staff_number") val staffNumber: String? = null,
    val role: String? = null,
    val gross: Double = 0.0,
    val tax: Double = 0.0,
    val pension: Double = 0.0,
    @param:Json(name = "other_deductions") val otherDeductions: Double = 0.0,
    val deductions: Double = 0.0,
    val net: Double = 0.0,
    @param:Json(name = "payment_status") val paymentStatus: String = "pending",
    @param:Json(name = "deduction_breakdown") val deductionBreakdown: List<PayrollDeductionLineDto> = emptyList(),
)

data class PayrollDetailDto(
    val capabilities: PayrollCapabilitiesDto = PayrollCapabilitiesDto(),
    val period: PayrollPeriodDto,
    val items: List<PayrollItemDto> = emptyList(),
)

data class PayrollMutationResponseDto(
    val message: String,
    val period: PayrollPeriodDto,
)

data class PayrollGenerateRequestDto(
    val title: String,
    @param:Json(name = "period_start") val periodStart: String,
    @param:Json(name = "period_end") val periodEnd: String,
)

data class PayrollGenerateResponseDto(
    val message: String,
    val period: PayrollPeriodDto,
    @param:Json(name = "skipped_staff") val skippedStaff: List<String> = emptyList(),
)
