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
    fun generic_operations_module_is_native_generic() {
        assertEquals(
            ModulePresentation.NATIVE_GENERIC,
            ModulePresentationPolicy.presentationFor("fees"),
        )
    }

    @Test
    fun role_dependent_module_is_explicitly_classified() {
        assertEquals(
            ModulePresentation.ROLE_CONDITIONAL,
            ModulePresentationPolicy.presentationFor("reports"),
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
