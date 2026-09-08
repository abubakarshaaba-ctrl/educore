package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.HealthDashboardDto
import online.educoreng.educore.core.network.dto.HealthDetailDto
import online.educoreng.educore.core.network.dto.HealthRecordUpdateRequestDto
import online.educoreng.educore.core.network.dto.HealthRecordUpdateResponseDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface HealthApi {
    @GET("health-officer/dashboard")
    suspend fun dashboard(
        @Query("search") search: String? = null,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 40,
    ): HealthDashboardDto

    @GET("health-officer/students/{student}")
    suspend fun show(@Path("student") studentId: Long): HealthDetailDto

    @POST("health-officer/students/{student}")
    suspend fun update(
        @Path("student") studentId: Long,
        @Body request: HealthRecordUpdateRequestDto,
    ): HealthRecordUpdateResponseDto
}
