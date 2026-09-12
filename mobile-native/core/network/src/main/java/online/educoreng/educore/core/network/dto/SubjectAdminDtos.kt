package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class SubjectCapabilitiesDto(
    val manage: Boolean = false,
)

data class SubjectMetricsDto(
    val total: Int = 0,
    val active: Int = 0,
    val inactive: Int = 0,
)

data class SubjectReferencesDto(
    @param:Json(name = "class_assignments") val classAssignments: Int = 0,
    @param:Json(name = "curriculum_rules") val curriculumRules: Int = 0,
    val scores: Int = 0,
    @param:Json(name = "student_selections") val studentSelections: Int = 0,
) {
    val total: Int get() = classAssignments + curriculumRules + scores + studentSelections
}

data class SubjectAdminDto(
    val id: Long,
    val name: String,
    val code: String? = null,
    val active: Boolean = true,
    val references: SubjectReferencesDto = SubjectReferencesDto(),
)

data class SubjectOptionDto(
    val key: String,
    val label: String,
)

data class SubjectSelectedDto(
    val search: String = "",
    val status: String = "all",
)

data class SubjectMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 30,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class SubjectsWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: SubjectCapabilitiesDto = SubjectCapabilitiesDto(),
    val metrics: SubjectMetricsDto = SubjectMetricsDto(),
    @param:Json(name = "status_options") val statusOptions: List<SubjectOptionDto> = emptyList(),
    val subjects: List<SubjectAdminDto> = emptyList(),
    val selected: SubjectSelectedDto = SubjectSelectedDto(),
    val meta: SubjectMetaDto = SubjectMetaDto(),
)

/**
 * Compatibility shape for older live servers that expose subjects through
 * /admin/management but do not yet expose the dedicated /subjects contract.
 * Unknown management fields are ignored by Moshi.
 */
data class LegacyAdminManagementDto(
    val subjects: List<LegacyAdminSubjectDto> = emptyList(),
)

data class LegacyAdminSubjectDto(
    val id: Long,
    val name: String,
    val code: String? = null,
)

data class SubjectMutationRequestDto(
    val name: String,
    val code: String? = null,
    @param:Json(name = "is_active") val isActive: Boolean,
)

data class SubjectMutationResponseDto(
    val message: String,
    val subject: SubjectAdminDto? = null,
)
