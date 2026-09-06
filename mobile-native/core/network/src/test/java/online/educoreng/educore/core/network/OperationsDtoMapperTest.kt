package online.educoreng.educore.core.network

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Test
import online.educoreng.educore.core.network.dto.OperationsFieldDto
import online.educoreng.educore.core.network.dto.OperationsMetricDto
import online.educoreng.educore.core.network.dto.OperationsModuleDto
import online.educoreng.educore.core.network.dto.OperationsRecordDto
import online.educoreng.educore.core.network.dto.OperationsResponseDto
import online.educoreng.educore.core.network.dto.OperationsSectionDto
import online.educoreng.educore.core.network.dto.toDomain

class OperationsDtoMapperTest {
    @Test
    fun `maps the generic read first operations contract without losing fields`() {
        val domain = OperationsResponseDto(
            contractVersion = 1,
            module = OperationsModuleDto("fees", "Fees & payments", "Invoices", false, "read_first"),
            metrics = listOf(OperationsMetricDto("balance", "Outstanding", "₦60,000.00", "currency", "warning")),
            sections = listOf(
                OperationsSectionDto(
                    "invoices",
                    "Invoices",
                    1,
                    listOf(
                        OperationsRecordDto(
                            "12",
                            "INV-001",
                            "Amina Bello",
                            "partially_paid",
                            listOf(OperationsFieldDto("Balance", "₦60,000.00")),
                        ),
                    ),
                ),
            ),
            generatedAt = "2026-08-31T12:00:00+01:00",
        ).toDomain()

        assertEquals("fees", domain.module.key)
        assertFalse(domain.module.canManage)
        assertEquals("₦60,000.00", domain.metrics.single().value)
        assertEquals("Amina Bello", domain.sections.single().records.single().subtitle)
        assertEquals("Balance", domain.sections.single().records.single().fields.single().label)
    }
}
