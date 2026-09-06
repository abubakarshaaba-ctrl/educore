package online.educoreng.educore.presentation

import org.junit.Assert.assertEquals
import org.junit.Test
import online.educoreng.educore.core.model.OperationsField
import online.educoreng.educore.core.model.OperationsRecord

class OperationsFilterTest {
    private val records = listOf(
        OperationsRecord("1", "INV-001", "Amina Bello", "partially_paid", listOf(OperationsField("Balance", "₦60,000.00"))),
        OperationsRecord("2", "Things Fall Apart", "Chinua Achebe", "active", listOf(OperationsField("Category", "Literature"))),
    )

    @Test
    fun `search covers title subtitle status label and value`() {
        assertEquals("INV-001", records.filterOperations("Amina").single().title)
        assertEquals("Things Fall Apart", records.filterOperations("literature").single().title)
        assertEquals("INV-001", records.filterOperations("60,000").single().title)
        assertEquals(2, records.filterOperations("").size)
    }
}
