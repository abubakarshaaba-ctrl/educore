package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class HostelCapabilitiesDto(val manage: Boolean = false)

data class HostelMetricsDto(
    val hostels: Int = 0,
    val rooms: Int = 0,
    val capacity: Int = 0,
    val residents: Int = 0,
)

data class HostelRoomDto(
    val id: Long,
    @param:Json(name = "hostel_id") val hostelId: Long,
    @param:Json(name = "room_number") val roomNumber: String,
    val capacity: Int,
    val occupied: Int = 0,
    val full: Boolean = false,
)

data class HostelDto(
    val id: Long,
    val name: String,
    val gender: String,
    val capacity: Int,
    val occupied: Int = 0,
    @param:Json(name = "warden_id") val wardenId: Long? = null,
    val warden: String? = null,
    val rooms: List<HostelRoomDto> = emptyList(),
)

data class HostelAllocationDto(
    val id: Long,
    @param:Json(name = "student_id") val studentId: Long,
    val student: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "hostel_id") val hostelId: Long,
    val hostel: String? = null,
    @param:Json(name = "room_id") val roomId: Long,
    val room: String? = null,
    @param:Json(name = "allocated_at") val allocatedAt: String? = null,
    val status: String = "active",
)

data class HostelStudentDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "class") val className: String? = null,
)

data class HostelWardenDto(
    val id: Long,
    val name: String,
    @param:Json(name = "staff_id") val staffId: String? = null,
)

data class HostelSelectedDto(
    val search: String = "",
    @param:Json(name = "student_search") val studentSearch: String = "",
)

data class HostelMetaDto(
    val page: Int = 1,
    @param:Json(name = "per_page") val perPage: Int = 40,
    val total: Int = 0,
    @param:Json(name = "last_page") val lastPage: Int = 1,
    @param:Json(name = "has_more") val hasMore: Boolean = false,
)

data class HostelWorkspaceDto(
    val capabilities: HostelCapabilitiesDto = HostelCapabilitiesDto(),
    val metrics: HostelMetricsDto = HostelMetricsDto(),
    val hostels: List<HostelDto> = emptyList(),
    val allocations: List<HostelAllocationDto> = emptyList(),
    @param:Json(name = "unallocated_students") val unallocatedStudents: List<HostelStudentDto> = emptyList(),
    val wardens: List<HostelWardenDto> = emptyList(),
    val selected: HostelSelectedDto = HostelSelectedDto(),
    val meta: HostelMetaDto = HostelMetaDto(),
)

data class CreateHostelRequestDto(
    val name: String,
    val gender: String,
    val capacity: Int,
    @param:Json(name = "warden_id") val wardenId: Long? = null,
)

data class CreateHostelResponseDto(
    val message: String,
    val hostel: HostelDto,
)

data class CreateHostelRoomRequestDto(
    @param:Json(name = "room_number") val roomNumber: String,
    val capacity: Int,
)

data class CreateHostelRoomResponseDto(
    val message: String,
    val room: HostelRoomDto,
)

data class HostelAllocationRequestDto(
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "hostel_id") val hostelId: Long,
    @param:Json(name = "room_id") val roomId: Long,
)

data class HostelAllocationResponseDto(
    val message: String,
    val allocation: HostelAllocationDto,
)

data class HostelMutationResponseDto(val message: String)
