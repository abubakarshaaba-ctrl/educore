package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class RiskModuleDto(
    val key: String,
    val title: String,
    val description: String,
    @param:Json(name = "mobile_policy") val mobilePolicy: String,
)

data class RiskCapabilitiesDto(
    val compute: Boolean = false,
    val acknowledge: Boolean = false,
    val resolve: Boolean = false,
    @param:Json(name = "manage_config") val manageConfig: Boolean = false,
)

data class RiskTermOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_name") val sessionName: String? = null,
    val current: Boolean = false,
)

data class RiskKeyLabelDto(
    val key: String,
    val label: String,
)

data class RiskFiltersDto(
    val terms: List<RiskTermOptionDto> = emptyList(),
    val statuses: List<RiskKeyLabelDto> = emptyList(),
    @param:Json(name = "risk_levels") val riskLevels: List<RiskKeyLabelDto> = emptyList(),
)

data class RiskSelectedDto(
    @param:Json(name = "term_id") val termId: Long? = null,
    val status: String = "open",
    @param:Json(name = "risk_level") val riskLevel: String = "all",
)

data class RiskSummaryDto(
    val total: Int = 0,
    val critical: Int = 0,
    val high: Int = 0,
    val medium: Int = 0,
    val low: Int = 0,
    val open: Int = 0,
    val acknowledged: Int = 0,
    val resolved: Int = 0,
)

data class RiskConfigDto(
    @param:Json(name = "academic_threshold") val academicThreshold: Double,
    @param:Json(name = "attendance_threshold") val attendanceThreshold: Double,
    @param:Json(name = "subjects_failed_threshold") val subjectsFailedThreshold: Int,
    @param:Json(name = "include_fee_risk") val includeFeeRisk: Boolean,
    @param:Json(name = "academic_weight") val academicWeight: Int,
    @param:Json(name = "attendance_weight") val attendanceWeight: Int,
    @param:Json(name = "fee_weight") val feeWeight: Int,
)

data class RiskStudentDto(
    val id: Long? = null,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class_name") val className: String? = null,
)

data class RiskTermDto(
    val id: Long,
    val name: String? = null,
)

data class RiskFlagDto(
    val id: Long,
    val student: RiskStudentDto,
    val term: RiskTermDto,
    @param:Json(name = "academic_risk") val academicRisk: Int,
    @param:Json(name = "attendance_risk") val attendanceRisk: Int,
    @param:Json(name = "fee_risk") val feeRisk: Int,
    @param:Json(name = "subjects_failed") val subjectsFailed: Int,
    @param:Json(name = "composite_risk") val compositeRisk: Int,
    @param:Json(name = "risk_level") val riskLevel: String,
    val flags: List<String> = emptyList(),
    @param:Json(name = "flag_labels") val flagLabels: List<String> = emptyList(),
    val status: String,
    @param:Json(name = "intervention_note") val interventionNote: String? = null,
    @param:Json(name = "computed_at") val computedAt: String? = null,
    @param:Json(name = "acknowledged_at") val acknowledgedAt: String? = null,
    @param:Json(name = "resolved_at") val resolvedAt: String? = null,
    @param:Json(name = "acknowledged_by") val acknowledgedBy: String? = null,
    @param:Json(name = "resolved_by") val resolvedBy: String? = null,
)

data class RiskPageMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 30,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class RiskListResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val module: RiskModuleDto,
    val capabilities: RiskCapabilitiesDto = RiskCapabilitiesDto(),
    val filters: RiskFiltersDto = RiskFiltersDto(),
    val selected: RiskSelectedDto = RiskSelectedDto(),
    val summary: RiskSummaryDto = RiskSummaryDto(),
    val config: RiskConfigDto,
    val flags: List<RiskFlagDto> = emptyList(),
    val meta: RiskPageMetaDto = RiskPageMetaDto(),
    @param:Json(name = "generated_at") val generatedAt: String,
)

data class RiskContextDto(
    @param:Json(name = "attendance_total") val attendanceTotal: Int = 0,
    @param:Json(name = "attendance_present") val attendancePresent: Int = 0,
    @param:Json(name = "attendance_rate") val attendanceRate: Double? = null,
    @param:Json(name = "score_records") val scoreRecords: Int = 0,
    @param:Json(name = "score_average") val scoreAverage: Double? = null,
    @param:Json(name = "previous_term_average") val previousTermAverage: Double? = null,
    @param:Json(name = "outstanding_invoices") val outstandingInvoices: Int = 0,
    @param:Json(name = "outstanding_balance") val outstandingBalance: Double = 0.0,
)

data class RiskDetailResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val flag: RiskFlagDto,
    val context: RiskContextDto,
    @param:Json(name = "generated_at") val generatedAt: String,
)

data class RiskInterventionRequestDto(
    @param:Json(name = "intervention_note") val interventionNote: String? = null,
)

data class RiskMutationResponseDto(
    val message: String,
    val flag: RiskFlagDto,
)

data class RiskComputeRequestDto(
    @param:Json(name = "term_id") val termId: Long,
    @param:Json(name = "class_level_id") val classLevelId: Long? = null,
    @param:Json(name = "class_arm_id") val classArmId: Long? = null,
)

data class RiskComputeResponseDto(
    val message: String,
    val created: Int = 0,
    val updated: Int = 0,
    val cleared: Int = 0,
    val processed: Int = 0,
)

data class RiskConfigUpdateRequestDto(
    @param:Json(name = "academic_threshold") val academicThreshold: Double,
    @param:Json(name = "attendance_threshold") val attendanceThreshold: Double,
    @param:Json(name = "subjects_failed_threshold") val subjectsFailedThreshold: Int,
    @param:Json(name = "include_fee_risk") val includeFeeRisk: Boolean,
    @param:Json(name = "academic_weight") val academicWeight: Int,
    @param:Json(name = "attendance_weight") val attendanceWeight: Int,
    @param:Json(name = "fee_weight") val feeWeight: Int,
)

data class RiskConfigResponseDto(
    val message: String,
    val config: RiskConfigDto,
)
