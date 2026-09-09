package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class PlatformInvoiceSummaryDto(
    @param:Json(name = "total_invoiced") val totalInvoiced: Double = 0.0,
    @param:Json(name = "total_paid") val totalPaid: Double = 0.0,
    @param:Json(name = "total_overdue") val totalOverdue: Double = 0.0,
    @param:Json(name = "pending_count") val pendingCount: Int = 0,
)

data class PlatformInvoiceDto(
    val id: Long,
    @param:Json(name = "tenant_id") val tenantId: Long,
    val school: String? = null,
    @param:Json(name = "invoice_number") val invoiceNumber: String,
    val amount: Double = 0.0,
    @param:Json(name = "student_count") val studentCount: Int = 0,
    @param:Json(name = "billing_cycle") val billingCycle: String,
    val status: String,
    @param:Json(name = "due_date") val dueDate: String? = null,
    @param:Json(name = "paid_at") val paidAt: String? = null,
    @param:Json(name = "payment_method") val paymentMethod: String? = null,
    @param:Json(name = "payment_ref") val paymentRef: String? = null,
    val notes: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
)

data class PlatformInvoiceTenantOptionDto(
    val id: Long,
    val name: String,
    val status: String,
)

data class PlatformInvoiceSelectedDto(
    val status: String = "all",
    @param:Json(name = "tenant_id") val tenantId: Long? = null,
)

data class PlatformInvoicesDto(
    val summary: PlatformInvoiceSummaryDto = PlatformInvoiceSummaryDto(),
    val invoices: List<PlatformInvoiceDto> = emptyList(),
    val tenants: List<PlatformInvoiceTenantOptionDto> = emptyList(),
    val selected: PlatformInvoiceSelectedDto = PlatformInvoiceSelectedDto(),
)

data class PlatformInvoiceCreateRequestDto(
    @param:Json(name = "tenant_id") val tenantId: Long,
    @param:Json(name = "billing_cycle") val billingCycle: String,
    val capacity: Int,
    @param:Json(name = "due_date") val dueDate: String,
    val notes: String? = null,
)

data class PlatformInvoiceCreateResponseDto(
    val message: String,
    val invoice: PlatformInvoiceDto,
)

data class PlatformInvoiceSettleRequestDto(
    @param:Json(name = "payment_method") val paymentMethod: String,
    @param:Json(name = "payment_ref") val paymentRef: String? = null,
)

data class PlatformInvoiceSettledTenantDto(
    val id: Long,
    val name: String,
    val status: String,
    @param:Json(name = "subscription_expires_at") val subscriptionExpiresAt: String? = null,
    @param:Json(name = "students_capacity") val studentsCapacity: Int? = null,
)

data class PlatformInvoiceSettleResponseDto(
    val message: String,
    val processed: Boolean,
    val invoice: PlatformInvoiceDto,
    val tenant: PlatformInvoiceSettledTenantDto,
)
