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
    fun cbt_modules_are_explicitly_unsupported_on_android() {
        listOf("cbt", "cbt-exams", "examinations", "student.exams", "student.cbt", "staff.cbt").forEach { key ->
            assertEquals(
                "$key must be absent from the Android product",
                ModulePresentation.UNSUPPORTED,
                ModulePresentationPolicy.presentationFor(key),
            )
            assertFalse(ModulePresentationPolicy.allowsBrowserHandoff(key))
        }
    }

    @Test
    fun student_results_alias_is_not_an_android_result_entry_point() {
        assertEquals(
            ModulePresentation.UNSUPPORTED,
            ModulePresentationPolicy.presentationFor("student.results"),
        )
        assertEquals(
            ModulePresentation.ROLE_CONDITIONAL,
            ModulePresentationPolicy.presentationFor("parent.results"),
        )
        assertEquals(
            ModulePresentation.ROLE_CONDITIONAL,
            ModulePresentationPolicy.presentationFor("report-cards"),
        )
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
