package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PlatformInvoiceCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformInvoiceCreateResponseDto
import online.educoreng.educore.core.network.dto.PlatformInvoiceSettleRequestDto
import online.educoreng.educore.core.network.dto.PlatformInvoiceSettleResponseDto
import online.educoreng.educore.core.network.dto.PlatformInvoicesDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface PlatformBillingApi {
    @GET("platform/billing/invoices")
    suspend fun invoices(
        @Query("status") status: String? = null,
        @Query("tenant_id") tenantId: Long? = null,
    ): PlatformInvoicesDto

    @POST("platform/billing/invoices")
    suspend fun createInvoice(@Body body: PlatformInvoiceCreateRequestDto): PlatformInvoiceCreateResponseDto

    @POST("platform/billing/invoices/{invoice}/settle")
    suspend fun settleInvoice(
        @Path("invoice") invoice: Long,
        @Body body: PlatformInvoiceSettleRequestDto,
    ): PlatformInvoiceSettleResponseDto
}
