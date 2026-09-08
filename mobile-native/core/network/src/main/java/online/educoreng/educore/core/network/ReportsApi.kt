package online.educoreng.educore.core.network

import okhttp3.ResponseBody
import online.educoreng.educore.core.network.dto.ReportComputeResponseDto
import online.educoreng.educore.core.network.dto.ReportMutationResponseDto
import online.educoreng.educore.core.network.dto.ReportPublishRequestDto
import online.educoreng.educore.core.network.dto.ReportsWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query
import retrofit2.http.Streaming

interface ReportsApi {
    @GET("reports")
    suspend fun index(
        @Query("class_arm_id") classArmId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): ReportsWorkspaceDto

    @POST("reports/compute")
    suspend fun compute(@Body body: ReportPublishRequestDto): ReportComputeResponseDto

    @POST("reports/publish")
    suspend fun publish(@Body body: ReportPublishRequestDto): ReportMutationResponseDto

    @POST("reports/unpublish")
    suspend fun unpublish(@Body body: ReportPublishRequestDto): ReportMutationResponseDto

    @Streaming
    @GET("reports/{summary}/pdf")
    suspend fun downloadPdf(@Path("summary") summaryId: Long): ResponseBody
}
