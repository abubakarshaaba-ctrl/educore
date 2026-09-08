package online.educoreng.educore.core.network

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Test
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST

class StaffCbtApiContractTest {
    @Test
    fun `staff CBT endpoints match the Laravel mobile contract`() {
        val methods = StaffCbtApi::class.java.declaredMethods.associateBy { it.name }

        assertEquals(
            "staff/cbt/exams",
            methods.getValue("exams").getAnnotation(GET::class.java).value,
        )
        assertEquals(
            "staff/cbt/exams/{exam}",
            methods.getValue("exam").getAnnotation(GET::class.java).value,
        )
        assertEquals(
            "staff/cbt/exams/{exam}/publish",
            methods.getValue("publish").getAnnotation(POST::class.java).value,
        )
        assertEquals(
            "staff/cbt/exams/{exam}/close",
            methods.getValue("close").getAnnotation(POST::class.java).value,
        )

        val reschedule = methods.getValue("reschedule")
        val patch = reschedule.getAnnotation(PATCH::class.java)
        assertNotNull("Rescheduling must use PATCH, not the retired POST URL.", patch)
        assertEquals("staff/cbt/exams/{exam}/schedule", patch.value)
        assertNull(reschedule.getAnnotation(POST::class.java))
    }
}
