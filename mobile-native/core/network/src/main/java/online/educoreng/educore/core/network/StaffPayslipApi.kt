package online.educoreng.educore.core.network

import okhttp3.ResponseBody
import online.educoreng.educore.core.network.dto.StaffPayslipDetailDto
import online.educoreng.educore.core.network.dto.StaffPayslipsResponseDto
import retrofit2.http.GET
import retrofit2.http.Path
import retrofit2.http.Streaming

interface StaffPayslipApi {
    @GET("payslips")
    suspend fun payslips(): StaffPayslipsResponseDto

    @GET("payslips/{item}")
    suspend fun payslip(@Path("item") itemId: Long): StaffPayslipDetailDto

    @Streaming
    @GET("payslips/{item}/pdf")
    suspend fun payslipPdf(@Path("item") itemId: Long): ResponseBody
}
