package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.OperationsField
import online.educoreng.educore.core.model.OperationsMetric
import online.educoreng.educore.core.model.OperationsModule
import online.educoreng.educore.core.model.OperationsRecord
import online.educoreng.educore.core.model.OperationsSection
import online.educoreng.educore.core.model.OperationsWorkspace

data class OperationsModuleDto(
    val key: String,
    val title: String,
    val description: String,
    @param:Json(name = "can_manage") val canManage: Boolean,
    @param:Json(name = "mobile_policy") val mobilePolicy: String,
)

data class OperationsMetricDto(
    val key: String,
    val label: String,
    val value: String,
    val format: String,
    val tone: String,
)

data class OperationsFieldDto(val label: String, val value: String)

data class OperationsRecordDto(
    val id: String,
    val title: String,
    val subtitle: String? = null,
    val status: String? = null,
    val fields: List<OperationsFieldDto> = emptyList(),
)

data class OperationsSectionDto(
    val key: String,
    val title: String,
    val count: Int,
    val records: List<OperationsRecordDto> = emptyList(),
)

data class OperationsResponseDto(
    @param:Json(name = "contract_version") val contractVersion: Int,
    val module: OperationsModuleDto,
    val metrics: List<OperationsMetricDto> = emptyList(),
    val sections: List<OperationsSectionDto> = emptyList(),
    @param:Json(name = "generated_at") val generatedAt: String,
)

fun OperationsResponseDto.toDomain() = OperationsWorkspace(
    module = OperationsModule(module.key, module.title, module.description, module.canManage, module.mobilePolicy),
    metrics = metrics.map { OperationsMetric(it.key, it.label, it.value, it.format, it.tone) },
    sections = sections.map { section ->
        OperationsSection(
            section.key,
            section.title,
            section.count,
            section.records.map { record ->
                OperationsRecord(
                    record.id,
                    record.title,
                    record.subtitle,
                    record.status,
                    record.fields.map { OperationsField(it.label, it.value) },
                )
            },
        )
    },
    generatedAt = generatedAt,
)
