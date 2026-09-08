package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class TransferCapabilitiesDto(
    @param:Json(name = "cross_school_request") val crossSchoolRequest: Boolean = false,
    @param:Json(name = "cross_school_approve") val crossSchoolApprove: Boolean = false,
    @param:Json(name = "cross_school_reject") val crossSchoolReject: Boolean = false,
    @param:Json(name = "interclass_view") val interclassView: Boolean = false,
    @param:Json(name = "interclass_request") val interclassRequest: Boolean = false,
    @param:Json(name = "interclass_approve") val interclassApprove: Boolean = false,
    @param:Json(name = "interclass_reject") val interclassReject: Boolean = false,
    @param:Json(name = "interclass_cancel") val interclassCancel: Boolean = false,
    @param:Json(name = "interclass_mobile_mutation") val interclassMobileMutation: Boolean = false,
)

data class TransferMetricsDto(
    @param:Json(name = "cross_outgoing") val crossOutgoing: Int = 0,
    @param:Json(name = "cross_incoming") val crossIncoming: Int = 0,
    @param:Json(name = "cross_pending") val crossPending: Int = 0,
    @param:Json(name = "interclass_pending") val interclassPending: Int = 0,
    @param:Json(name = "interclass_completed") val interclassCompleted: Int = 0,
)

data class CrossSchoolTransferDto(
    val id: Long,
    val direction: String,
    @param:Json(name = "student_id") val studentId: Long? = null,
    @param:Json(name = "student_name") val studentName: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "from_school") val fromSchool: String,
    @param:Json(name = "to_school") val toSchool: String,
    val status: String,
    val reason: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
)

data class InterclassTransferDto(
    val id: Long,
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "student_name") val studentName: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "from_class") val fromClass: String? = null,
    @param:Json(name = "to_class") val toClass: String? = null,
    @param:Json(name = "effective_date") val effectiveDate: String? = null,
    val status: String,
    val reason: String? = null,
    @param:Json(name = "requested_by") val requestedBy: String? = null,
    @param:Json(name = "created_at") val createdAt: String? = null,
)

data class TransferDestinationOptionDto(
    val id: Long,
    val name: String,
)

data class TransferStudentOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class_arm_id") val classArmId: Long? = null,
)

data class TransferClassOptionDto(
    val id: Long,
    val name: String,
)

data class TransferOptionsDto(
    val destinations: List<TransferDestinationOptionDto> = emptyList(),
    val students: List<TransferStudentOptionDto> = emptyList(),
    @param:Json(name = "class_arms") val classArms: List<TransferClassOptionDto> = emptyList(),
)

data class TransfersWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val capabilities: TransferCapabilitiesDto = TransferCapabilitiesDto(),
    val metrics: TransferMetricsDto = TransferMetricsDto(),
    @param:Json(name = "cross_school") val crossSchool: List<CrossSchoolTransferDto> = emptyList(),
    val interclass: List<InterclassTransferDto> = emptyList(),
    val options: TransferOptionsDto = TransferOptionsDto(),
)

data class CrossSchoolTransferRequestDto(
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "to_tenant_id") val toTenantId: Long,
    val reason: String? = null,
)

data class TransferMutationResponseDto(
    val message: String,
    @param:Json(name = "transfer_id") val transferId: Long? = null,
)
