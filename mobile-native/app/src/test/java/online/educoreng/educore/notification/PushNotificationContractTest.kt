package online.educoreng.educore.notification

import org.junit.Assert.assertEquals
import org.junit.Test

class PushNotificationContractTest {
    @Test
    fun `canonical channels and app update type stay stable`() {
        assertEquals("educore_notifications", PushNotificationContract.GENERAL_CHANNEL_ID)
        assertEquals("educore_app_updates", PushNotificationContract.APP_UPDATES_TOPIC)
        assertEquals("app_update", PushNotificationContract.APP_UPDATE_TYPE)
        assertEquals("title", PushNotificationContract.KEY_TITLE)
        assertEquals("body", PushNotificationContract.KEY_BODY)
    }
}
