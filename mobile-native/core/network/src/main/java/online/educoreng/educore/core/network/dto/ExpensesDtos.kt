package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class ExpensesCapabilitiesDto(val manage: Boolean = false)

data class ExpensesMetricsDto(
    val total: Double = 0.0,
    val records: Int = 0,
)

data class ExpenseCategoryDto(
    val key: String,
    val label: String,
)

data class ExpenseSessionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class ExpenseTermDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_id") val sessionId: Long,
    val session: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
)

data class ExpenseDto(
    val id: Long,
    val title: String,
    val category: String,
    val amount: Double,
    @param:Json(name = "expense_date") val expenseDate: String,
    @param:Json(name = "payment_method") val paymentMethod: String? = null,
    val reference: String? = null,
    val description: String? = null,
    @param:Json(name = "session_id") val sessionId: Long? = null,
    val session: String? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
    val term: String? = null,
    @param:Json(name = "recorded_by") val recordedBy: Long? = null,
)

data class ExpensesSelectedDto(
    val search: String = "",
    val category: String = "all",
)

data class ExpensesMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 30,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class ExpensesWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: ExpensesCapabilitiesDto = ExpensesCapabilitiesDto(),
    val metrics: ExpensesMetricsDto = ExpensesMetricsDto(),
    @param:Json(name = "category_totals") val categoryTotals: Map<String, Double> = emptyMap(),
    val categories: List<ExpenseCategoryDto> = emptyList(),
    val sessions: List<ExpenseSessionDto> = emptyList(),
    val terms: List<ExpenseTermDto> = emptyList(),
    val expenses: List<ExpenseDto> = emptyList(),
    val selected: ExpensesSelectedDto = ExpensesSelectedDto(),
    val meta: ExpensesMetaDto = ExpensesMetaDto(),
)

data class ExpenseRequestDto(
    val title: String,
    val category: String,
    val amount: Double,
    @param:Json(name = "expense_date") val expenseDate: String,
    @param:Json(name = "payment_method") val paymentMethod: String? = null,
    val reference: String? = null,
    @param:Json(name = "term_id") val termId: Long? = null,
    @param:Json(name = "session_id") val sessionId: Long? = null,
    val description: String? = null,
)

data class ExpenseMutationResponseDto(
    val message: String,
    val expense: ExpenseDto? = null,
)
