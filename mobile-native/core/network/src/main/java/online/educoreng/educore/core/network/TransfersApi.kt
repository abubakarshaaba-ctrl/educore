package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.CrossSchoolTransferRequestDto
import online.educoreng.educore.core.network.dto.TransferMutationResponseDto
import online.educoreng.educore.core.network.dto.TransfersWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface TransfersApi {
    @GET("transfers")
    suspend fun index(
        @Query("scope") scope: String? = null,
        @Query("status") status: String? = null,
        @Query("q") query: String? = null,
        @Query("limit") limit: Int = 50,
    ): TransfersWorkspaceDto

    @POST("transfers/cross-school")
    suspend fun requestCrossSchool(@Body body: CrossSchoolTransferRequestDto): TransferMutationResponseDto

    @POST("transfers/cross-school/{transfer}/approve")
    suspend fun approveCrossSchool(@Path("transfer") transferId: Long): TransferMutationResponseDto

    @POST("transfers/cross-school/{transfer}/reject")
    suspend fun rejectCrossSchool(@Path("transfer") transferId: Long): TransferMutationResponseDto
}
