package online.educoreng.educore.core.network

import okhttp3.ResponseBody
import online.educoreng.educore.core.network.dto.ExportOptionsResponseDto
import retrofit2.http.GET
import retrofit2.http.Query
import retrofit2.http.Streaming

interface ExportsApi {
    @GET("operations/exports")
    suspend fun options(): ExportOptionsResponseDto

    @Streaming
    @GET("operations/exports")
    suspend fun download(
        @Query("action") action: String = "download",
        @Query("type") type: String,
        @Query("class_arm_id") classArmId: Long? = null,
        @Query("term_id") termId: Long? = null,
        @Query("session_id") sessionId: Long? = null,
    ): ResponseBody
}
