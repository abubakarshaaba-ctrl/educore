package online.educoreng.educore.notification

import java.io.File
import org.junit.Assert.assertTrue
import org.junit.Test

class NotificationSessionGuardTest {
    private val source by lazy {
        File("src/main/java/online/educoreng/educore/notification/EduCoreMessagingService.kt").readText()
    }

    @Test
    fun account_pushes_require_an_authenticated_local_session() {
        assertTrue(source.contains("@Inject lateinit var sessionRepository: SessionRepository"))
        assertTrue(source.contains("if (!isAppUpdate && !sessionRepository.hasStoredToken())"))
    }

    @Test
    fun refreshed_fcm_tokens_register_only_for_authenticated_sessions() {
        assertTrue(source.contains("if (sessionRepository.hasStoredToken())"))
        assertTrue(source.contains("communicationRepository.registerPushToken(token)"))
    }
}
