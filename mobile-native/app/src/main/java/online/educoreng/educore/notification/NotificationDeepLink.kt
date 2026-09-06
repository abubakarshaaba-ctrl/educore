package online.educoreng.educore.notification

import android.content.Intent
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import online.educoreng.educore.core.model.DeepLinkTarget

object NotificationDeepLinkParser {
    private val allowedTypes = setOf("announcement", "message_thread", "calendar_event")

    fun parse(values: Map<String, String>): DeepLinkTarget? {
        val type = values["destination_type"]
            ?.trim()
            ?.lowercase()
            ?.takeIf(allowedTypes::contains)
            ?: return null
        val id = values["destination_id"]
            ?.trim()
            ?.takeIf { value -> value.toLongOrNull()?.let { it > 0 } == true }
        if (type == "message_thread" && id == null) return null
        return DeepLinkTarget(type, id)
    }

    fun parse(intent: Intent?): DeepLinkTarget? = intent?.let {
        parse(
            mapOf(
                "destination_type" to it.getStringExtra("destination_type").orEmpty(),
                "destination_id" to it.getStringExtra("destination_id").orEmpty(),
            ),
        )
    }
}

object NotificationDeepLinkStore {
    private val _pending = MutableStateFlow<DeepLinkTarget?>(null)
    val pending = _pending.asStateFlow()

    fun publish(target: DeepLinkTarget?) {
        if (target != null) _pending.value = target
    }

    fun consume() {
        _pending.value = null
    }
}
