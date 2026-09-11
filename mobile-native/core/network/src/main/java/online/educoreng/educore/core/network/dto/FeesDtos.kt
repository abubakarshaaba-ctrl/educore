package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class FeesCapabilitiesDto(
    val manage: Boolean = false,
)

data class FeesMetricsDto(
    val billed: Double = 0.0,
    val collected: Double = 0.0,
    val outstanding: Double = 0.0,
    @param:Json(name = "successful_transactions") val successfulTransactions: Int = 0,
)

data class FeesOptionDto(
    val key: String,
    val label: String,
)

data class FeesTermDto(
    val id: Long,
    val name: String,
    val session: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class FeesClassLevelDto(
    val id: Long,
    val name: String,
)

data class FeesInvoiceDto(
    val id: Long,
    val number: String,
    val student: String? = null,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val term: String? = null,
    val session: String? = null,
    val total: Double = 0.0,
    val paid: Double = 0.0,
    val balance: Double = 0.0,
    val status: String,
    @param:Json(name = "due_date") val dueDate: String? = null,
)

data class FeesTransactionDto(
    val id: Long,
    @param:Json(name = "invoice_id") val invoiceId: Long,
    @param:Json(name = "invoice_number") val invoiceNumber: String? = null,
    val student: String? = null,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    val reference: String,
    val gateway: String,
    val amount: Double,
    val currency: String = "NGN",
    @param:Json(name = "paid_by_name") val paidByName: String? = null,
    @param:Json(name = "paid_by_phone") val paidByPhone: String? = null,
    @param:Json(name = "paid_at") val paidAt: String? = null,
)

data class FeesSelectedDto(
    val search: String = "",
    val status: String = "all",
)

data class FeesMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 30,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class FeesWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: FeesCapabilitiesDto = FeesCapabilitiesDto(),
    val metrics: FeesMetricsDto = FeesMetricsDto(),
    @param:Json(name = "status_options") val statusOptions: List<FeesOptionDto> = emptyList(),
    @param:Json(name = "current_term_id") val currentTermId: Long? = null,
    val terms: List<FeesTermDto> = emptyList(),
    @param:Json(name = "class_levels") val classLevels: List<FeesClassLevelDto> = emptyList(),
    val invoices: List<FeesInvoiceDto> = emptyList(),
    val transactions: List<FeesTransactionDto> = emptyList(),
    val selected: FeesSelectedDto = FeesSelectedDto(),
    val meta: FeesMetaDto = FeesMetaDto(),
)

data class FeesPaymentRequestDto(
    val amount: Double,
    @param:Json(name = "paid_by_name") val paidByName: String,
    @param:Json(name = "paid_by_phone") val paidByPhone: String? = null,
    val gateway: String,
)

data class FeesPaymentResultDto(
    val id: Long,
    val reference: String,
    val amount: Double,
    val gateway: String,
    @param:Json(name = "paid_at") val paidAt: String? = null,
)

data class FeesPaymentResponseDto(
    val message: String,
    val invoice: FeesInvoiceDto,
    val payment: FeesPaymentResultDto,
)

data class FeesGenerateRequestDto(
    @param:Json(name = "term_id") val termId: Long,
    @param:Json(name = "class_level_id") val classLevelId: Long,
)

data class FeesGenerateResponseDto(
    val message: String,
    val generated: Int,
    val skipped: Int,
)
