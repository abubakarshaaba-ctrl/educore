package online.educoreng.educore.presentation

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Test

class ModulePresentationPolicyTest {
    @Test
    fun known_native_module_is_native() {
        assertEquals(
            ModulePresentation.NATIVE,
            ModulePresentationPolicy.presentationFor("lesson-planner"),
        )
    }

    @Test
    fun staff_and_student_cbt_modules_are_native() {
        listOf("cbt", "cbt-exams", "examinations", "student.exams").forEach { key ->
            assertEquals(
                "$key must remain native",
                ModulePresentation.NATIVE,
                ModulePresentationPolicy.presentationFor(key),
            )
        }
    }

    @Test
    fun generic_operations_module_is_native_generic() {
        assertEquals(
            ModulePresentation.NATIVE_GENERIC,
            ModulePresentationPolicy.presentationFor("analytics"),
        )
    }

    @Test
    fun fully_native_operational_modules_remain_native() {
        listOf("fees", "reports").forEach { key ->
            assertEquals(
                "$key must remain fully native",
                ModulePresentation.NATIVE,
                ModulePresentationPolicy.presentationFor(key),
            )
        }
    }

    @Test
    fun role_dependent_module_is_explicitly_classified() {
        assertEquals(
            ModulePresentation.ROLE_CONDITIONAL,
            ModulePresentationPolicy.presentationFor("scores"),
        )
    }

    @Test
    fun unknown_module_is_unsupported_instead_of_web() {
        assertEquals(
            ModulePresentation.UNSUPPORTED,
            ModulePresentationPolicy.presentationFor("unknown-future-module"),
        )
        assertFalse(ModulePresentationPolicy.allowsBrowserHandoff("unknown-future-module"))
    }

    @Test
    fun browser_handoff_is_disabled_until_explicitly_approved() {
        assertFalse(ModulePresentationPolicy.allowsBrowserHandoff("cbt"))
        assertFalse(ModulePresentationPolicy.allowsBrowserHandoff("reports"))
        assertFalse(ModulePresentationPolicy.allowsBrowserHandoff("platform.settings"))
    }
}
