package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.GradebookRemarkRequestDto
import online.educoreng.educore.core.network.dto.GradebookRemarkResponseDto
import online.educoreng.educore.core.network.dto.GradebookWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface GradebookApi {
    @GET("gradebook")
    suspend fun index(
        @Query("class_arm_id") classArmId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): GradebookWorkspaceDto

    @PUT("gradebook/remarks/{summary}")
    suspend fun updateRemark(
        @Path("summary") summaryId: Long,
        @Body body: GradebookRemarkRequestDto,
    ): GradebookRemarkResponseDto
}
