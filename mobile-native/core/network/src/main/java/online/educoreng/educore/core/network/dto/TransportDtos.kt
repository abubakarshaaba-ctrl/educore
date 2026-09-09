package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class TransportCapabilitiesDto(
    val manage: Boolean = false,
)

data class TransportMetricsDto(
    val routes: Int = 0,
    @param:Json(name = "active_buses") val activeBuses: Int = 0,
    @param:Json(name = "assigned_students") val assignedStudents: Int = 0,
    @param:Json(name = "unassigned_students") val unassignedStudents: Int = 0,
)

data class TransportRouteDto(
    val id: Long,
    val name: String,
    val description: String? = null,
    val fare: Double = 0.0,
    @param:Json(name = "morning_time") val morningTime: String? = null,
    @param:Json(name = "evening_time") val eveningTime: String? = null,
    val active: Boolean = false,
    val bus: String = "Not assigned",
    val driver: String = "Not assigned",
    val assistant: String = "Not assigned",
    val riders: Int = 0,
    val capacity: Int? = null,
)

data class TransportBusDto(
    val id: Long,
    @param:Json(name = "plate_number") val plateNumber: String,
    val model: String? = null,
    val capacity: Int = 0,
    val year: Int? = null,
    val active: Boolean = false,
)

data class TransportStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class") val className: String? = null,
)

data class TransportSelectedDto(
    val search: String = "",
)

data class TransportMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 40,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class TransportDashboardDto(
    val capabilities: TransportCapabilitiesDto = TransportCapabilitiesDto(),
    val metrics: TransportMetricsDto = TransportMetricsDto(),
    val routes: List<TransportRouteDto> = emptyList(),
    val buses: List<TransportBusDto> = emptyList(),
    @param:Json(name = "unassigned_students") val unassignedStudents: List<TransportStudentDto> = emptyList(),
    val selected: TransportSelectedDto = TransportSelectedDto(),
    val meta: TransportMetaDto = TransportMetaDto(),
)

data class TransportManifestRouteDto(
    val id: Long,
    val name: String,
)

data class TransportManifestItemDto(
    @param:Json(name = "assignment_id") val assignmentId: Long = 0L,
    @param:Json(name = "student_id") val studentId: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class") val className: String? = null,
    @param:Json(name = "pickup_stop") val pickupStop: String? = null,
    val direction: String = "both",
)

data class TransportManifestDto(
    val capabilities: TransportCapabilitiesDto = TransportCapabilitiesDto(),
    val route: TransportManifestRouteDto,
    val manifest: List<TransportManifestItemDto> = emptyList(),
)

data class TransportAssignmentRequestDto(
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "route_id") val routeId: Long,
    @param:Json(name = "pickup_stop") val pickupStop: String? = null,
    val direction: String,
)

data class TransportMutationResponseDto(
    val message: String,
)
