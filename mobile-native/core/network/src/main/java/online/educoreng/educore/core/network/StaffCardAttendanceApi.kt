package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.StaffCardAttendanceScanRequestDto
import online.educoreng.educore.core.network.dto.StaffCardAttendanceScanResponseDto
import retrofit2.http.Body
import retrofit2.http.POST

interface StaffCardAttendanceApi {
    @POST("staff-attendance/scan-card")
    suspend fun scan(@Body body: StaffCardAttendanceScanRequestDto): StaffCardAttendanceScanResponseDto
}
