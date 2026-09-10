package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdminAttendanceManualRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceMutationResponseDto
import online.educoreng.educore.core.network.dto.AdminAttendanceProxyDecisionRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceReviewRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceSettingsRequestDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceQrDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceReportDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceResponseDto
import online.educoreng.educore.core.network.dto.AdminStaffOfflineQueueDto
import online.educoreng.educore.core.network.dto.AdminStaffProxyReviewQueueDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface AdminStaffAttendanceApi {
    @GET("admin/staff-attendance")
    suspend fun daily(
        @Query("date") date: String? = null,
        @Query("status") status: String? = null,
        @Query("q") query: String? = null,
    ): AdminStaffAttendanceResponseDto

    @GET("admin/staff-attendance/report")
    suspend fun report(
        @Query("month") month: Int,
        @Query("year") year: Int,
    ): AdminStaffAttendanceReportDto

    @POST("admin/staff-attendance/manual")
    suspend fun manualOverride(@Body body: AdminAttendanceManualRequestDto): AdminAttendanceMutationResponseDto

    @GET("admin/staff-attendance/offline")
    suspend fun offlineQueue(): AdminStaffOfflineQueueDto

    @POST("admin/staff-attendance/offline/{record}")
    suspend fun processOffline(
        @Path("record") recordId: Long,
        @Body body: AdminAttendanceReviewRequestDto,
    ): AdminAttendanceMutationResponseDto

    @GET("admin/staff-attendance/proxy-reviews")
    suspend fun proxyReviews(): AdminStaffProxyReviewQueueDto

    @POST("admin/staff-attendance/proxy-reviews/{record}")
    suspend fun decideProxy(
        @Path("record") recordId: Long,
        @Body body: AdminAttendanceProxyDecisionRequestDto,
    ): AdminAttendanceMutationResponseDto

    @GET("admin/staff-attendance/qr")
    suspend fun qr(): AdminStaffAttendanceQrDto

    @PUT("admin/staff-attendance/settings")
    suspend fun updateSettings(@Body body: AdminAttendanceSettingsRequestDto): AdminAttendanceMutationResponseDto

    @POST("admin/staff-attendance/reset-qr")
    suspend fun resetQr(): AdminAttendanceMutationResponseDto
}
