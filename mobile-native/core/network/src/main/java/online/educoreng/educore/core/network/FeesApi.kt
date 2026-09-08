package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.FeesGenerateRequestDto
import online.educoreng.educore.core.network.dto.FeesGenerateResponseDto
import online.educoreng.educore.core.network.dto.FeesPaymentRequestDto
import online.educoreng.educore.core.network.dto.FeesPaymentResponseDto
import online.educoreng.educore.core.network.dto.FeesWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface FeesApi {
    @GET("fees")
    suspend fun index(
        @Query("q") search: String? = null,
        @Query("status") status: String? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null,
    ): FeesWorkspaceDto

    @POST("fees/generate")
    suspend fun generate(@Body body: FeesGenerateRequestDto): FeesGenerateResponseDto

    @POST("fees/invoices/{invoice}/payments")
    suspend fun recordPayment(
        @Path("invoice") invoice: Long,
        @Body body: FeesPaymentRequestDto,
    ): FeesPaymentResponseDto
}
