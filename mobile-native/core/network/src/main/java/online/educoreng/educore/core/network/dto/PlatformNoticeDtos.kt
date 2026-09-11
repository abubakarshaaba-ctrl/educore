package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass

@JsonClass(generateAdapter = true)
data class PlatformNoticeFeedDto(
    val notices: List<PlatformNoticeDto> = emptyList(),
    @Json(name = "unread_count") val unreadCount: Int = 0,
)

@JsonClass(generateAdapter = true)
data class PlatformNoticeDto(
    val id: Long,
    val title: String,
    val body: String,
    val priority: String = "normal",
    val audience: String = "staff",
    @Json(name = "is_read") val isRead: Boolean = false,
    @Json(name = "created_at") val createdAt: String? = null,
    @Json(name = "expires_at") val expiresAt: String? = null,
    val source: String = "platform",
)

@JsonClass(generateAdapter = true)
data class PlatformNoticeMutationDto(
    val message: String? = null,
)
