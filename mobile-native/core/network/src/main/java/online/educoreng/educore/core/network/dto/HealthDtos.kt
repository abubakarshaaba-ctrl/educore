package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class HealthCapabilitiesDto(
    val manage: Boolean = false,
)

data class HealthMetricsDto(
    val students: Int = 0,
    val records: Int = 0,
    @param:Json(name = "allergy_alerts") val allergyAlerts: Int = 0,
    @param:Json(name = "medication_alerts") val medicationAlerts: Int = 0,
)

data class HealthStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class") val className: String? = null,
    @param:Json(name = "has_record") val hasRecord: Boolean = false,
    @param:Json(name = "allergy_alert") val allergyAlert: Boolean = false,
    @param:Json(name = "medication_alert") val medicationAlert: Boolean = false,
)

data class HealthSelectedDto(
    val search: String = "",
)

data class HealthMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 40,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class HealthDashboardDto(
    val capabilities: HealthCapabilitiesDto = HealthCapabilitiesDto(),
    val metrics: HealthMetricsDto = HealthMetricsDto(),
    val students: List<HealthStudentDto> = emptyList(),
    val selected: HealthSelectedDto = HealthSelectedDto(),
    val meta: HealthMetaDto = HealthMetaDto(),
)

data class HealthStudentSummaryDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class") val className: String? = null,
)

data class HealthRecordDto(
    @param:Json(name = "blood_group") val bloodGroup: String? = null,
    val genotype: String? = null,
    val allergies: String? = null,
    @param:Json(name = "chronic_conditions") val chronicConditions: String? = null,
    @param:Json(name = "current_medications") val currentMedications: String? = null,
    val disability: String? = null,
    @param:Json(name = "emergency_contact_name") val emergencyContactName: String? = null,
    @param:Json(name = "emergency_contact_phone") val emergencyContactPhone: String? = null,
    @param:Json(name = "emergency_contact_relationship") val emergencyContactRelationship: String? = null,
    @param:Json(name = "doctor_name") val doctorName: String? = null,
    @param:Json(name = "doctor_phone") val doctorPhone: String? = null,
    val notes: String? = null,
)

data class HealthDetailDto(
    val capabilities: HealthCapabilitiesDto = HealthCapabilitiesDto(),
    val student: HealthStudentSummaryDto,
    val record: HealthRecordDto = HealthRecordDto(),
)

data class HealthRecordUpdateRequestDto(
    @param:Json(name = "blood_group") val bloodGroup: String? = null,
    val genotype: String? = null,
    val allergies: String? = null,
    @param:Json(name = "chronic_conditions") val chronicConditions: String? = null,
    @param:Json(name = "current_medications") val currentMedications: String? = null,
    val disability: String? = null,
    @param:Json(name = "emergency_contact_name") val emergencyContactName: String? = null,
    @param:Json(name = "emergency_contact_phone") val emergencyContactPhone: String? = null,
    @param:Json(name = "emergency_contact_relationship") val emergencyContactRelationship: String? = null,
    @param:Json(name = "doctor_name") val doctorName: String? = null,
    @param:Json(name = "doctor_phone") val doctorPhone: String? = null,
    val notes: String? = null,
)

data class HealthRecordUpdateResponseDto(
    val message: String,
    val record: HealthRecordDto,
)
