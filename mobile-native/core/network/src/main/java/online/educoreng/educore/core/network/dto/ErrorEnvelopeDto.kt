package online.educoreng.educore.core.network.dto

data class ErrorEnvelopeDto(
    val message: String? = null,
    val state: String? = null,
    val errors: Map<String, List<String>>? = null,
)
