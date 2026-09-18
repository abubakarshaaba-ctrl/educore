package online.educoreng.educore.core.data.repository

import android.net.Uri
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.dto.ChangePasswordRequestDto
import online.educoreng.educore.core.network.dto.StaffIdCardDto
import online.educoreng.educore.core.network.dto.UpdateProfileRequestDto
import online.educoreng.educore.core.network.dto.UserProfileDto

/**
 * Contract for the canonical profile self-service flow.
 *
 * Keep the interface in core:data so the app presentation layer and Hilt
 * binding depend on the abstraction while the concrete implementation remains
 * replaceable and testable.
 */
interface ProfileSelfServiceRepository {
    suspend fun profile(): AppResult<UserProfileDto>
    suspend fun updateProfile(request: UpdateProfileRequestDto): AppResult<UserProfileDto>
    suspend fun changePassword(request: ChangePasswordRequestDto): AppResult<String>
    suspend fun uploadPassport(uri: Uri): AppResult<UserProfileDto>
    suspend fun staffIdCard(): AppResult<StaffIdCardDto>
    suspend fun downloadStaffIdCard(
        staffId: String?,
        staffName: String,
    ): AppResult<DownloadedDocument>
}
