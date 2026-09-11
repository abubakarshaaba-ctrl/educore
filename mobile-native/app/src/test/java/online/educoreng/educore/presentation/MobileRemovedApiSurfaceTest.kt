package online.educoreng.educore.presentation

import online.educoreng.educore.core.network.EduCoreApi
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class MobileRemovedApiSurfaceTest {
    @Test
    fun generic_android_api_exposes_no_cbt_calls() {
        val methodNames = EduCoreApi::class.java.methods.map { it.name.lowercase() }.toSet()

        assertFalse(methodNames.any { it.contains("cbt") })
        assertFalse(methodNames.any { it.contains("exam") && it !in setOf("equals") })
    }

    @Test
    fun published_result_transport_is_parent_only() {
        val methodNames = EduCoreApi::class.java.methods.map { it.name }.toSet()

        assertTrue("parentResults" in methodNames)
        assertFalse("studentResults" in methodNames)
        assertFalse("staffStudentResults" in methodNames)
    }
}
