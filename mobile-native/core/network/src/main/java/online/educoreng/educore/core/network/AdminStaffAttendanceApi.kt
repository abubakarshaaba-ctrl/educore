package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdminAttendanceManualRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceMutationResponseDto
import online.educoreng.educore.core.network.dto.AdminAttendanceOfflineSyncRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceProxyClockRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceProxyDecisionRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceReviewRequestDto
import online.educoreng.educore.core.network.dto.AdminAttendanceSettingsRequestDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceQrDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceReportDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceResponseDto
import online.educoreng.educore.core.network.dto.AdminStaffAttendanceSettingsResponseDto
import online.educoreng.educore.core.network.dto.AdminStaffOfflineQueueDto
import online.educoreng.educore.core.network.dto.AdminStaffProxyReviewQueueDto
import online.educoreng.educore.core.network.dto.SchoolOpenDaysRequestDto
import online.educoreng.educore.core.network.dto.SchoolOpenDaysResponseDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface AdminStaffAttendanceApi {
    @GET("admin/staff-attendance/daily")
    suspend fun daily(
        @Query("date") date: String? = null,
        @Query("status") status: String? = null,
        @Query("q") query: String? = null,
    ): AdminStaffAttendanceResponseDto

    @GET("admin/staff-attendance/monthly")
    suspend fun report(@Query("month") month: Int, @Query("year") year: Int): AdminStaffAttendanceReportDto

    @GET("admin/staff-attendance/settings")
    suspend fun settings(): AdminStaffAttendanceSettingsResponseDto

    @PUT("admin/staff-attendance/settings")
    suspend fun updateSettings(@Body body: AdminAttendanceSettingsRequestDto): AdminAttendanceMutationResponseDto

    @PUT("admin/staff-attendance/school-open-days")
    suspend fun updateSchoolOpenDays(@Body body: SchoolOpenDaysRequestDto): SchoolOpenDaysResponseDto

    @GET("admin/staff-attendance/offline")
    suspend fun offlineQueue(): AdminStaffOfflineQueueDto

    @POST("admin/staff-attendance/offline/sync")
    suspend fun syncOffline(@Body body: AdminAttendanceOfflineSyncRequestDto): AdminAttendanceMutationResponseDto

    @GET("admin/staff-attendance/proxy-reviews")
    suspend fun proxyReviews(): AdminStaffProxyReviewQueueDto

    @POST("admin/staff-attendance/proxy-clock")
    suspend fun proxyClock(@Body body: AdminAttendanceProxyClockRequestDto): AdminAttendanceMutationResponseDto

    @GET("admin/staff-attendance/qr")
    suspend fun qr(): AdminStaffAttendanceQrDto

    @POST("admin/staff-attendance/qr/reset")
    suspend fun resetQr(): AdminAttendanceMutationResponseDto

    // Compatibility operations retained only while old admin editing/review UI remains.
    @POST("admin/staff-attendance/manual")
    suspend fun manualOverride(@Body body: AdminAttendanceManualRequestDto): AdminAttendanceMutationResponseDto

    @POST("admin/staff-attendance/offline/{record}")
    suspend fun processOffline(@Path("record") recordId: Long, @Body body: AdminAttendanceReviewRequestDto): AdminAttendanceMutationResponseDto

    @POST("admin/staff-attendance/proxy-reviews/{record}")
    suspend fun decideProxy(@Path("record") recordId: Long, @Body body: AdminAttendanceProxyDecisionRequestDto): AdminAttendanceMutationResponseDto
}
