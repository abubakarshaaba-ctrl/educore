package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdmissionsWorkspaceDto
import online.educoreng.educore.core.network.dto.CreateAdmissionRequestDto
import online.educoreng.educore.core.network.dto.CreateAdmissionResponseDto
import online.educoreng.educore.core.network.dto.UpdateAdmissionStatusRequestDto
import online.educoreng.educore.core.network.dto.UpdateAdmissionStatusResponseDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface AdmissionsApi {
    @GET("admissions")
    suspend fun index(
        @Query("status") status: String = "all",
        @Query("search") search: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 30,
    ): AdmissionsWorkspaceDto

    @POST("admissions")
    suspend fun create(@Body request: CreateAdmissionRequestDto): CreateAdmissionResponseDto

    @PATCH("admissions/{admission}/status")
    suspend fun updateStatus(
        @Path("admission") admissionId: Long,
        @Body request: UpdateAdmissionStatusRequestDto,
    ): UpdateAdmissionStatusResponseDto
}
