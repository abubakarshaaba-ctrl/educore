package online.educoreng.educore.core.data.repository

import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.dto.StaffPayslipDetailDto
import online.educoreng.educore.core.network.dto.StaffPayslipSummaryDto

interface StaffPayslipRepository {
    suspend fun list(): AppResult<List<StaffPayslipSummaryDto>>
    suspend fun detail(id: Long): AppResult<StaffPayslipDetailDto>
    suspend fun downloadPdf(id: Long, periodTitle: String): AppResult<DownloadedDocument>
}
