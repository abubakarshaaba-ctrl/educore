package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class AdminStudentsResponseDto(
    @Json(name = "students") val students: List<AdminStudentDto> = emptyList(),
)

@JsonClass(generateAdapter = true)
data class AdminStudentDto(
    @Json(name = "id") val id: Long,
    @Json(name = "name") val name: String,
    @Json(name = "admission_number") val admissionNumber: String? = null,
    @Json(name = "class") val className: String? = null,
    @Json(name = "gender") val gender: String? = null,
    @Json(name = "status") val status: String? = null,
)
