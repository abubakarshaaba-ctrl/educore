package online.educoreng.educore.core.network

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface MobilePaymentsApi {
    @GET("admin/subscription")
    suspend fun subscription(): SubscriptionWorkspaceDto

    @POST("admin/subscription/invoices")
    suspend fun createSubscriptionInvoice(@Body request: SubscriptionInvoiceRequestDto): SubscriptionInvoiceResponseDto

    @POST("admin/subscription/invoices/{invoice}/checkout")
    suspend fun subscriptionCheckout(
        @Path("invoice") invoiceId: Long,
        @Body request: GatewayRequestDto,
    ): CheckoutResponseDto

    @POST("admin/subscription/verify")
    suspend fun verifySubscription(@Body request: VerifyPaymentRequestDto): SubscriptionVerifyResponseDto

    @GET("parent/fees")
    suspend fun parentFees(@Query("child_id") childId: Long? = null): ParentFeesWorkspaceDto

    @POST("parent/fees/{invoice}/checkout")
    suspend fun parentFeeCheckout(
        @Path("invoice") invoiceId: Long,
        @Body request: EmptyRequestDto = EmptyRequestDto(),
    ): CheckoutResponseDto

    @POST("parent/fees/verify")
    suspend fun verifyParentFee(@Body request: VerifyPaymentRequestDto): ParentFeeVerifyResponseDto
}

@JsonClass(generateAdapter = true)
data class EmptyRequestDto(val mobile: Boolean = true)

@JsonClass(generateAdapter = true)
data class GatewayRequestDto(val gateway: String)

@JsonClass(generateAdapter = true)
data class VerifyPaymentRequestDto(val reference: String)

@JsonClass(generateAdapter = true)
data class SubscriptionInvoiceRequestDto(
    @Json(name = "billing_cycle") val billingCycle: String,
    @Json(name = "anticipated_enrollment") val anticipatedEnrollment: Int,
)

@JsonClass(generateAdapter = true)
data class CheckoutResponseDto(
    val provider: String,
    val reference: String,
    @Json(name = "checkout_url") val checkoutUrl: String,
    val amount: Double? = null,
    val currency: String? = null,
)

@JsonClass(generateAdapter = true)
data class SubscriptionTenantDto(
    val id: Long? = null,
    val name: String? = null,
    val status: String,
    @Json(name = "subscription_expires_at") val subscriptionExpiresAt: String? = null,
    @Json(name = "students_capacity") val studentsCapacity: Int,
    @Json(name = "active_students") val activeStudents: Int? = null,
)

@JsonClass(generateAdapter = true)
data class SubscriptionPricingDto(
    @Json(name = "free_threshold") val freeThreshold: Int,
    @Json(name = "rate_per_student_per_term") val ratePerStudentPerTerm: Double,
    @Json(name = "termly_amount") val termlyAmount: Double,
)

@JsonClass(generateAdapter = true)
data class SubscriptionInvoiceDto(
    val id: Long,
    val number: String,
    val amount: Double,
    @Json(name = "billing_cycle") val billingCycle: String,
    val status: String,
    @Json(name = "due_date") val dueDate: String? = null,
    @Json(name = "paid_at") val paidAt: String? = null,
    @Json(name = "payment_method") val paymentMethod: String? = null,
    @Json(name = "payment_reference") val paymentReference: String? = null,
)

@JsonClass(generateAdapter = true)
data class SubscriptionWorkspaceDto(
    @Json(name = "contract_version") val contractVersion: Int,
    val tenant: SubscriptionTenantDto,
    val pricing: SubscriptionPricingDto,
    val gateways: List<String> = emptyList(),
    val invoices: List<SubscriptionInvoiceDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class SubscriptionInvoiceResponseDto(
    val free: Boolean,
    val message: String? = null,
    val amount: Double? = null,
    val invoice: SubscriptionInvoiceDto? = null,
)

@JsonClass(generateAdapter = true)
data class SubscriptionVerifyResponseDto(
    val message: String,
    val tenant: SubscriptionTenantDto,
    val invoice: SubscriptionInvoiceDto,
)

@JsonClass(generateAdapter = true)
data class ParentFeeChildDto(
    val id: Long,
    val name: String,
    @Json(name = "admission_number") val admissionNumber: String? = null,
)

@JsonClass(generateAdapter = true)
data class ParentFeeGatewayDto(
    val name: String,
    val available: Boolean,
)

@JsonClass(generateAdapter = true)
data class ParentFeeInvoiceDto(
    val id: Long,
    val number: String,
    val term: String? = null,
    val session: String? = null,
    @Json(name = "total_amount") val totalAmount: Double,
    @Json(name = "amount_paid") val amountPaid: Double,
    val balance: Double,
    val status: String,
    @Json(name = "due_date") val dueDate: String? = null,
)

@JsonClass(generateAdapter = true)
data class ParentFeesWorkspaceDto(
    @Json(name = "contract_version") val contractVersion: Int,
    val children: List<ParentFeeChildDto> = emptyList(),
    @Json(name = "selected_child_id") val selectedChildId: Long? = null,
    val gateway: ParentFeeGatewayDto? = null,
    val invoices: List<ParentFeeInvoiceDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class ParentFeeVerifyResponseDto(
    val message: String,
    val invoice: ParentFeeInvoiceDto,
)
