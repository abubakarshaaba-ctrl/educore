package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json

data class LibraryBookOptionDto(
    val id: Long,
    val title: String,
    val author: String? = null,
    @param:Json(name = "available_copies") val availableCopies: Int = 0,
)

data class LibraryBorrowerOptionDto(
    val id: Long,
    val name: String,
    val reference: String? = null,
)

data class LibraryOptionsDto(
    val books: List<LibraryBookOptionDto> = emptyList(),
    val students: List<LibraryBorrowerOptionDto> = emptyList(),
    val staff: List<LibraryBorrowerOptionDto> = emptyList(),
)

data class LibraryIssueRequestDto(
    @param:Json(name = "book_id") val bookId: Long,
    @param:Json(name = "student_id") val studentId: Long? = null,
    @param:Json(name = "staff_id") val staffId: Long? = null,
    @param:Json(name = "due_date") val dueDate: String,
    val notes: String? = null,
)

data class LibraryLoanMutationDto(
    val id: Long,
    @param:Json(name = "book_id") val bookId: Long? = null,
    @param:Json(name = "student_id") val studentId: Long? = null,
    @param:Json(name = "staff_id") val staffId: Long? = null,
    @param:Json(name = "due_date") val dueDate: String? = null,
    val status: String,
    @param:Json(name = "return_date") val returnDate: String? = null,
)

data class LibraryMutationResponseDto(
    val message: String,
    val loan: LibraryLoanMutationDto,
)
