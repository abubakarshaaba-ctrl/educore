package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.StaffCbtExamResponseDto
import online.educoreng.educore.core.network.dto.StaffCbtExamsResponseDto
import online.educoreng.educore.core.network.dto.StaffCbtMutationResponseDto
import online.educoreng.educore.core.network.dto.StaffCbtRescheduleRequestDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

/** Native staff-only CBT management contract. Server-side RBAC remains authoritative. */
interface StaffCbtApi {
    @GET("staff/cbt/exams")
    suspend fun exams(
        @Query("status") status: String? = null,
        @Query("query") query: String? = null,
    ): StaffCbtExamsResponseDto

    @GET("staff/cbt/exams/{exam}")
    suspend fun exam(@Path("exam") examId: Long): StaffCbtExamResponseDto

    @POST("staff/cbt/exams/{exam}/publish")
    suspend fun publish(@Path("exam") examId: Long): StaffCbtMutationResponseDto

    @POST("staff/cbt/exams/{exam}/close")
    suspend fun close(@Path("exam") examId: Long): StaffCbtMutationResponseDto

    @POST("staff/cbt/exams/{exam}/reschedule")
    suspend fun reschedule(
        @Path("exam") examId: Long,
        @Body request: StaffCbtRescheduleRequestDto,
    ): StaffCbtMutationResponseDto
}
