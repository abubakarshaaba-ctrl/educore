package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PayrollDetailDto
import online.educoreng.educore.core.network.dto.PayrollGenerateRequestDto
import online.educoreng.educore.core.network.dto.PayrollGenerateResponseDto
import online.educoreng.educore.core.network.dto.PayrollMutationResponseDto
import online.educoreng.educore.core.network.dto.PayrollWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface PayrollApi {
    @GET("payroll")
    suspend fun index(
        @Query("q") search: String? = null,
        @Query("status") status: String? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null,
    ): PayrollWorkspaceDto

    @POST("payroll")
    suspend fun generate(@Body request: PayrollGenerateRequestDto): PayrollGenerateResponseDto

    @GET("payroll/{period}")
    suspend fun show(@Path("period") period: Long): PayrollDetailDto

    @POST("payroll/{period}/approve")
    suspend fun approve(@Path("period") period: Long): PayrollMutationResponseDto

    @POST("payroll/{period}/paid")
    suspend fun markPaid(@Path("period") period: Long): PayrollMutationResponseDto
}
