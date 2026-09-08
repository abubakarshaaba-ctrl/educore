package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.RiskComputeRequestDto
import online.educoreng.educore.core.network.dto.RiskComputeResponseDto
import online.educoreng.educore.core.network.dto.RiskConfigResponseDto
import online.educoreng.educore.core.network.dto.RiskConfigUpdateRequestDto
import online.educoreng.educore.core.network.dto.RiskDetailResponseDto
import online.educoreng.educore.core.network.dto.RiskInterventionRequestDto
import online.educoreng.educore.core.network.dto.RiskListResponseDto
import online.educoreng.educore.core.network.dto.RiskMutationResponseDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface RiskApi {
    @GET("risk")
    suspend fun flags(
        @Query("term_id") termId: Long? = null,
        @Query("status") status: String? = null,
        @Query("risk_level") riskLevel: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 30,
    ): RiskListResponseDto

    @GET("risk/{flag}")
    suspend fun flag(@Path("flag") flagId: Long): RiskDetailResponseDto

    @POST("risk/compute")
    suspend fun compute(@Body request: RiskComputeRequestDto): RiskComputeResponseDto

    @POST("risk/{flag}/acknowledge")
    suspend fun acknowledge(
        @Path("flag") flagId: Long,
        @Body request: RiskInterventionRequestDto,
    ): RiskMutationResponseDto

    @POST("risk/{flag}/resolve")
    suspend fun resolve(
        @Path("flag") flagId: Long,
        @Body request: RiskInterventionRequestDto,
    ): RiskMutationResponseDto

    @PUT("risk/config")
    suspend fun updateConfig(@Body request: RiskConfigUpdateRequestDto): RiskConfigResponseDto
}
