package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdmissionDetailResponseDto
import online.educoreng.educore.core.network.dto.AdmissionMutationResponseDto
import online.educoreng.educore.core.network.dto.AdmissionsWorkspaceDto
import online.educoreng.educore.core.network.dto.CreateAdmissionRequestDto
import online.educoreng.educore.core.network.dto.CreateAdmissionResponseDto
import online.educoreng.educore.core.network.dto.RecordAdmissionInterviewRequestDto
import online.educoreng.educore.core.network.dto.ScheduleAdmissionInterviewRequestDto
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

    @GET("admissions/{admission}")
    suspend fun show(@Path("admission") admissionId: Long): AdmissionDetailResponseDto

    @POST("admissions")
    suspend fun create(@Body request: CreateAdmissionRequestDto): CreateAdmissionResponseDto

    @PATCH("admissions/{admission}/status")
    suspend fun updateStatus(
        @Path("admission") admissionId: Long,
        @Body request: UpdateAdmissionStatusRequestDto,
    ): UpdateAdmissionStatusResponseDto

    @POST("admissions/{admission}/interview")
    suspend fun scheduleInterview(
        @Path("admission") admissionId: Long,
        @Body request: ScheduleAdmissionInterviewRequestDto,
    ): AdmissionMutationResponseDto

    @POST("admissions/{admission}/interview/result")
    suspend fun recordInterview(
        @Path("admission") admissionId: Long,
        @Body request: RecordAdmissionInterviewRequestDto,
    ): AdmissionMutationResponseDto

    @POST("admissions/{admission}/offer")
    suspend fun sendOffer(@Path("admission") admissionId: Long): AdmissionMutationResponseDto
}
