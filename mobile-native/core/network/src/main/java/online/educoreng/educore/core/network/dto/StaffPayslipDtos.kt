package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class StaffPayslipsResponseDto(
    val payslips: List<StaffPayslipSummaryDto> = emptyList(),
)

data class StaffPayslipSummaryDto(
    val id: Long,
    @Json(name = "period_id") val periodId: Long,
    @Json(name = "period_title") val periodTitle: String,
    @Json(name = "net_pay") val netPay: Double,
    @Json(name = "gross_pay") val grossPay: Double,
    val status: String? = null,
)

data class StaffPayslipDetailDto(
    val id: Long,
    @Json(name = "period_title") val periodTitle: String? = null,
    val status: String? = null,
    val earnings: StaffPayslipEarningsDto,
    val deductions: StaffPayslipDeductionsDto,
    @Json(name = "net_pay") val netPay: Double,
    val bank: StaffPayslipBankDto? = null,
)

data class StaffPayslipEarningsDto(
    @Json(name = "basic_salary") val basicSalary: Double = 0.0,
    @Json(name = "housing_allowance") val housingAllowance: Double = 0.0,
    @Json(name = "transport_allowance") val transportAllowance: Double = 0.0,
    @Json(name = "other_allowances") val otherAllowances: Double = 0.0,
    @Json(name = "gross_pay") val grossPay: Double = 0.0,
)

data class StaffPayslipDeductionsDto(
    @Json(name = "tax_deduction") val taxDeduction: Double = 0.0,
    @Json(name = "pension_deduction") val pensionDeduction: Double = 0.0,
    @Json(name = "other_deductions") val otherDeductions: Double = 0.0,
    @Json(name = "total_deductions") val totalDeductions: Double = 0.0,
)

data class StaffPayslipBankDto(
    val name: String? = null,
    val account: String? = null,
)
