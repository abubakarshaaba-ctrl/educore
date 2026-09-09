package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class AdmissionModuleDto(
    val key: String = "admissions",
    val title: String = "Admissions",
    val description: String = "",
    @param:Json(name = "mobile_policy") val mobilePolicy: String = "native",
)

data class AdmissionCapabilitiesDto(
    val create: Boolean = false,
    @param:Json(name = "change_status") val changeStatus: Boolean = false,
    @param:Json(name = "schedule_interview") val scheduleInterview: Boolean = false,
    @param:Json(name = "record_interview") val recordInterview: Boolean = false,
)

data class AdmissionStatsDto(
    val total: Int = 0,
    val pending: Int = 0,
    val shortlisted: Int = 0,
    val admitted: Int = 0,
    val rejected: Int = 0,
    val withdrawn: Int = 0,
)

data class AdmissionKeyLabelDto(
    val key: String,
    val label: String,
)

data class AdmissionClassLevelDto(
    val id: Long,
    val name: String,
)

data class AdmissionClassArmDto(
    val id: Long,
    val name: String,
)

data class AdmissionItemDto(
    val id: Long,
    @param:Json(name = "application_number") val applicationNumber: String = "",
    val name: String = "",
    @param:Json(name = "first_name") val firstName: String = "",
    @param:Json(name = "last_name") val lastName: String = "",
    @param:Json(name = "other_names") val otherNames: String? = null,
    val gender: String? = null,
    @param:Json(name = "date_of_birth") val dateOfBirth: String? = null,
    @param:Json(name = "class_level_id") val classLevelId: Long? = null,
    @param:Json(name = "class_level") val classLevel: String? = null,
    @param:Json(name = "guardian_name") val guardianName: String = "",
    @param:Json(name = "guardian_phone") val guardianPhone: String = "",
    @param:Json(name = "guardian_email") val guardianEmail: String? = null,
    val status: String = "pending",
    val notes: String? = null,
    @param:Json(name = "application_date") val applicationDate: String? = null,
    @param:Json(name = "interview_date") val interviewDate: String? = null,
    @param:Json(name = "interview_score") val interviewScore: Double? = null,
    @param:Json(name = "offer_letter_sent") val offerLetterSent: Boolean = false,
    @param:Json(name = "enrolled_student_id") val enrolledStudentId: Long? = null,
    @param:Json(name = "guardian_relationship") val guardianRelationship: String? = null,
    val address: String? = null,
    @param:Json(name = "interview_notes") val interviewNotes: String? = null,
    @param:Json(name = "decision_date") val decisionDate: String? = null,
)

data class AdmissionSelectedDto(
    val status: String = "all",
    val search: String = "",
)

data class AdmissionMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 30,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class AdmissionsWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    val module: AdmissionModuleDto = AdmissionModuleDto(),
    val capabilities: AdmissionCapabilitiesDto = AdmissionCapabilitiesDto(),
    val stats: AdmissionStatsDto = AdmissionStatsDto(),
    @param:Json(name = "status_options") val statusOptions: List<AdmissionKeyLabelDto> = emptyList(),
    @param:Json(name = "class_levels") val classLevels: List<AdmissionClassLevelDto> = emptyList(),
    @param:Json(name = "class_arms") val classArms: List<AdmissionClassArmDto> = emptyList(),
    val admissions: List<AdmissionItemDto> = emptyList(),
    val selected: AdmissionSelectedDto = AdmissionSelectedDto(),
    val meta: AdmissionMetaDto = AdmissionMetaDto(),
    @param:Json(name = "generated_at") val generatedAt: String = "",
)

data class CreateAdmissionRequestDto(
    @param:Json(name = "first_name") val firstName: String,
    @param:Json(name = "last_name") val lastName: String,
    @param:Json(name = "other_names") val otherNames: String? = null,
    @param:Json(name = "date_of_birth") val dateOfBirth: String,
    val gender: String,
    @param:Json(name = "applying_for_class_level_id") val applyingForClassLevelId: Long? = null,
    @param:Json(name = "guardian_name") val guardianName: String,
    @param:Json(name = "guardian_phone") val guardianPhone: String,
    @param:Json(name = "guardian_email") val guardianEmail: String? = null,
    @param:Json(name = "guardian_relationship") val guardianRelationship: String,
    val address: String? = null,
    val notes: String? = null,
)

data class CreateAdmissionResponseDto(
    val message: String,
    val admission: AdmissionItemDto,
)

data class UpdateAdmissionStatusRequestDto(
    val status: String,
    val notes: String? = null,
    @param:Json(name = "class_arm_id") val classArmId: Long? = null,
)

data class UpdateAdmissionStatusResponseDto(
    val message: String,
    val admission: AdmissionItemDto,
)
