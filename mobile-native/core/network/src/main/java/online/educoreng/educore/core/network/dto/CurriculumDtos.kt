package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class CurriculumCapabilitiesDto(val manage: Boolean = false)

data class CurriculumMetricsDto(
    val tracks: Int = 0,
    @param:Json(name = "school_tracks") val schoolTracks: Int = 0,
    val rules: Int = 0,
    @param:Json(name = "active_rules") val activeRules: Int = 0,
)

data class CurriculumTrackReferencesDto(
    @param:Json(name = "class_arms") val classArms: Int = 0,
    val rules: Int = 0,
    @param:Json(name = "student_selections") val studentSelections: Int = 0,
) {
    val total: Int get() = classArms + rules + studentSelections
}

data class CurriculumTrackDto(
    val id: Long,
    val name: String,
    val section: String,
    val active: Boolean = true,
    val system: Boolean = false,
    val manageable: Boolean = false,
    val references: CurriculumTrackReferencesDto = CurriculumTrackReferencesDto(),
)

data class CurriculumRuleDto(
    val id: Long,
    @param:Json(name = "class_level_id") val classLevelId: Long,
    @param:Json(name = "class_level") val classLevel: String? = null,
    @param:Json(name = "track_id") val trackId: Long? = null,
    val track: String = "All tracks",
    @param:Json(name = "subject_id") val subjectId: Long,
    val subject: String? = null,
    @param:Json(name = "subject_code") val subjectCode: String? = null,
    val status: String,
    @param:Json(name = "elective_group") val electiveGroup: String? = null,
    @param:Json(name = "min_required") val minRequired: Int? = null,
    @param:Json(name = "max_allowed") val maxAllowed: Int? = null,
    val active: Boolean = true,
)

data class CurriculumEntityOptionDto(
    val id: Long,
    val name: String,
    val code: String? = null,
)

data class CurriculumKeyOptionDto(val key: String, val label: String)

data class CurriculumSelectedDto(
    val search: String = "",
    @param:Json(name = "level_id") val levelId: Long? = null,
    @param:Json(name = "track_id") val trackId: Long? = null,
    val status: String = "all",
)

data class CurriculumWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: CurriculumCapabilitiesDto = CurriculumCapabilitiesDto(),
    val metrics: CurriculumMetricsDto = CurriculumMetricsDto(),
    val tracks: List<CurriculumTrackDto> = emptyList(),
    val rules: List<CurriculumRuleDto> = emptyList(),
    @param:Json(name = "class_levels") val classLevels: List<CurriculumEntityOptionDto> = emptyList(),
    val subjects: List<CurriculumEntityOptionDto> = emptyList(),
    @param:Json(name = "status_options") val statusOptions: List<CurriculumKeyOptionDto> = emptyList(),
    @param:Json(name = "section_options") val sectionOptions: List<CurriculumKeyOptionDto> = emptyList(),
    val selected: CurriculumSelectedDto = CurriculumSelectedDto(),
)

data class CurriculumTrackRequestDto(
    val name: String,
    val section: String,
    @param:Json(name = "is_active") val isActive: Boolean,
)

data class CurriculumRuleCreateRequestDto(
    @param:Json(name = "class_level_id") val classLevelId: Long,
    @param:Json(name = "academic_track_id") val academicTrackId: Long? = null,
    @param:Json(name = "subject_id") val subjectId: Long,
    @param:Json(name = "subject_status") val subjectStatus: String,
    @param:Json(name = "elective_group") val electiveGroup: String? = null,
    @param:Json(name = "min_required") val minRequired: Int? = null,
    @param:Json(name = "max_allowed") val maxAllowed: Int? = null,
)

data class CurriculumRuleUpdateRequestDto(
    @param:Json(name = "subject_status") val subjectStatus: String,
    @param:Json(name = "elective_group") val electiveGroup: String? = null,
    @param:Json(name = "min_required") val minRequired: Int? = null,
    @param:Json(name = "max_allowed") val maxAllowed: Int? = null,
    @param:Json(name = "is_active") val isActive: Boolean,
)

data class CurriculumTrackMutationResponseDto(
    val message: String,
    val track: CurriculumTrackDto,
)

data class CurriculumRuleMutationResponseDto(
    val message: String,
    val rule: CurriculumRuleDto,
)

data class CurriculumMessageDto(val message: String)
