package online.educoreng.educore.core.data.repository

import android.content.Context
import com.squareup.moshi.Moshi
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.StaffPayslipApi
import online.educoreng.educore.core.network.dto.StaffPayslipDetailDto
import online.educoreng.educore.core.network.dto.StaffPayslipSummaryDto
import online.educoreng.educore.core.network.safeApiCall

class DefaultStaffPayslipRepository(
    private val context: Context,
    private val api: StaffPayslipApi,
    private val moshi: Moshi,
) : StaffPayslipRepository {
    override suspend fun list(): AppResult<List<StaffPayslipSummaryDto>> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.payslips() }) {
            is AppResult.Success -> AppResult.Success(result.value.payslips)
            is AppResult.Failure -> result
        }
    }

    override suspend fun detail(id: Long): AppResult<StaffPayslipDetailDto> = withContext(Dispatchers.IO) {
        safeApiCall(moshi) { api.payslip(id) }
    }

    override suspend fun downloadPdf(id: Long, periodTitle: String): AppResult<DownloadedDocument> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.payslipPdf(id) }) {
            is AppResult.Success -> runCatching {
                saveDownloadedDocument(
                    context = context,
                    body = result.value,
                    requestedName = "Payslip_${slug(periodTitle)}.pdf",
                    requestedMimeType = "application/pdf",
                )
            }.fold(
                onSuccess = { AppResult.Success(it) },
                onFailure = { AppResult.Failure(AppError.Unexpected("The payslip could not be saved.", it)) },
            )
            is AppResult.Failure -> result
        }
    }

    private fun slug(value: String): String = value
        .trim()
        .replace(Regex("[^A-Za-z0-9]+"), "_")
        .trim('_')
        .ifBlank { "EduCore" }
}
