package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.CrossSchoolTransferRequestDto
import online.educoreng.educore.core.network.dto.InterclassTransferRequestDto
import online.educoreng.educore.core.network.dto.TransferMutationResponseDto
import online.educoreng.educore.core.network.dto.TransferReasonRequestDto
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

    @POST("transfers/intra-class")
    suspend fun requestIntraClass(@Body body: InterclassTransferRequestDto): TransferMutationResponseDto

    @POST("transfers/intra-class/{transfer}/approve")
    suspend fun approveIntraClass(@Path("transfer") transferId: Long): TransferMutationResponseDto

    @POST("transfers/intra-class/{transfer}/reject")
    suspend fun rejectIntraClass(
        @Path("transfer") transferId: Long,
        @Body body: TransferReasonRequestDto,
    ): TransferMutationResponseDto

    @POST("transfers/intra-class/{transfer}/cancel")
    suspend fun cancelIntraClass(
        @Path("transfer") transferId: Long,
        @Body body: TransferReasonRequestDto,
    ): TransferMutationResponseDto

    @POST("transfers/interclass")
    suspend fun requestInterclass(@Body body: InterclassTransferRequestDto): TransferMutationResponseDto

    @POST("transfers/interclass/{transfer}/approve")
    suspend fun approveInterclass(@Path("transfer") transferId: Long): TransferMutationResponseDto

    @POST("transfers/interclass/{transfer}/reject")
    suspend fun rejectInterclass(
        @Path("transfer") transferId: Long,
        @Body body: TransferReasonRequestDto,
    ): TransferMutationResponseDto

    @POST("transfers/interclass/{transfer}/cancel")
    suspend fun cancelInterclass(
        @Path("transfer") transferId: Long,
        @Body body: TransferReasonRequestDto,
    ): TransferMutationResponseDto
}
