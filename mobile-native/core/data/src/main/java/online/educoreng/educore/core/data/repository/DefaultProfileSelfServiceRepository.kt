package online.educoreng.educore.core.data.repository

import android.content.Context
import android.net.Uri
import com.squareup.moshi.Moshi
import java.io.ByteArrayOutputStream
import java.io.IOException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.model.DownloadedDocument
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.ChangePasswordRequestDto
import online.educoreng.educore.core.network.dto.StaffIdCardDto
import online.educoreng.educore.core.network.dto.UpdateProfileRequestDto
import online.educoreng.educore.core.network.dto.UserProfileDto
import online.educoreng.educore.core.network.safeApiCall

class DefaultProfileSelfServiceRepository(
    private val context: Context,
    private val api: EduCoreApi,
    private val moshi: Moshi,
) : ProfileSelfServiceRepository {
    override suspend fun profile(): AppResult<UserProfileDto> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.profile() }) {
            is AppResult.Success -> AppResult.Success(result.value.profile)
            is AppResult.Failure -> result
        }
    }

    override suspend fun updateProfile(request: UpdateProfileRequestDto): AppResult<UserProfileDto> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.updateProfile(request) }) {
            is AppResult.Success -> AppResult.Success(result.value.profile)
            is AppResult.Failure -> result
        }
    }

    override suspend fun changePassword(request: ChangePasswordRequestDto): AppResult<String> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.changePassword(request) }) {
            is AppResult.Success -> AppResult.Success(result.value.message)
            is AppResult.Failure -> result
        }
    }

    override suspend fun uploadPassport(uri: Uri): AppResult<UserProfileDto> = withContext(Dispatchers.IO) {
        runCatching {
            val resolver = context.contentResolver
            val mimeType = resolver.getType(uri)?.takeIf { it.startsWith("image/") } ?: "image/jpeg"
            val bytes = resolver.openInputStream(uri)?.use(::readImageBounded)
                ?: throw IOException("The selected passport image could not be opened.")
            val extension = when (mimeType.lowercase()) {
                "image/png" -> "png"
                "image/webp" -> "webp"
                else -> "jpg"
            }
            val requestBody = bytes.toRequestBody(mimeType.toMediaTypeOrNull())
            MultipartBody.Part.createFormData("passport", "passport.$extension", requestBody)
        }.fold(
            onSuccess = { part ->
                when (val result = safeApiCall(moshi) { api.uploadPassport(part) }) {
                    is AppResult.Success -> AppResult.Success(result.value.profile)
                    is AppResult.Failure -> result
                }
            },
            onFailure = { AppResult.Failure(AppError.Unexpected(it.message ?: "The passport image could not be prepared.", it)) },
        )
    }

    override suspend fun staffIdCard(): AppResult<StaffIdCardDto> = withContext(Dispatchers.IO) {
        safeApiCall(moshi) { api.staffIdCard() }
    }

    override suspend fun downloadStaffIdCard(staffId: String?, staffName: String): AppResult<DownloadedDocument> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.staffIdCardPdf() }) {
            is AppResult.Success -> runCatching {
                val identity = staffId?.takeIf(String::isNotBlank) ?: staffName
                saveDownloadedDocument(
                    context = context,
                    body = result.value,
                    requestedName = "Staff_ID_${slug(identity)}.pdf",
                    requestedMimeType = "application/pdf",
                )
            }.fold(
                onSuccess = { AppResult.Success(it) },
                onFailure = { AppResult.Failure(AppError.Unexpected("The staff ID card could not be saved.", it)) },
            )
            is AppResult.Failure -> result
        }
    }

    private fun readImageBounded(input: java.io.InputStream): ByteArray {
        val output = ByteArrayOutputStream()
        val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
        var total = 0
        while (true) {
            val count = input.read(buffer)
            if (count < 0) break
            total += count
            if (total > MAX_PASSPORT_BYTES) {
                throw IOException("Passport image must not exceed 4 MB.")
            }
            output.write(buffer, 0, count)
        }
        return output.toByteArray()
    }

    private fun slug(value: String): String = value
        .trim()
        .replace(Regex("[^A-Za-z0-9]+"), "_")
        .trim('_')
        .ifBlank { "EduCore" }

    private companion object {
        const val MAX_PASSPORT_BYTES = 4 * 1024 * 1024
    }
}
