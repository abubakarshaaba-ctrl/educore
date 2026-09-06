package online.educoreng.educore.core.security

import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

class AndroidKeystoreTokenVault(private val context: Context) : TokenVault {
    private val preferences = context.getSharedPreferences(PREFERENCES_NAME, Context.MODE_PRIVATE)
    private val legacyPreferences by lazy {
        context.getSharedPreferences(LEGACY_PREFERENCES_NAME, Context.MODE_PRIVATE)
    }
    private val lock = Any()

    override fun bearerToken(): String? = synchronized(lock) {
        val cipherText = preferences.getString(KEY_CIPHER_TEXT, null)
            ?: return@synchronized migrateLegacyToken()
        val initializationVector = preferences.getString(KEY_INITIALIZATION_VECTOR, null)
            ?: return@synchronized null

        runCatching {
            val cipher = Cipher.getInstance(TRANSFORMATION)
            cipher.init(
                Cipher.DECRYPT_MODE,
                secretKey(),
                GCMParameterSpec(GCM_TAG_LENGTH_BITS, Base64.decode(initializationVector, Base64.NO_WRAP)),
            )
            String(
                cipher.doFinal(Base64.decode(cipherText, Base64.NO_WRAP)),
                Charsets.UTF_8,
            ).also { clearLegacySession() }
        }.getOrElse {
            clear()
            null
        }
    }

    override fun save(token: String) {
        synchronized(lock) {
            require(token.isNotBlank()) { "A blank bearer token cannot be stored." }
            encryptAndPersist(token)
            check(clearLegacySession()) { "The legacy plaintext session could not be removed." }
        }
    }

    override fun clear() {
        synchronized(lock) {
            preferences.edit()
                .remove(KEY_CIPHER_TEXT)
                .remove(KEY_INITIALIZATION_VECTOR)
                .commit()
            check(clearLegacySession()) { "The legacy plaintext session could not be removed." }
        }
    }

    private fun migrateLegacyToken(): String? {
        val token = runCatching {
            legacyPreferences.getString(LEGACY_TOKEN_KEY, null)
                ?: legacyPreferences.getString(LEGACY_UNPREFIXED_TOKEN_KEY, null)
        }.getOrNull()?.takeIf(String::isNotBlank) ?: return null

        return runCatching {
            encryptAndPersist(token)
            check(clearLegacySession()) { "The legacy plaintext session could not be removed." }
            token
        }.getOrNull()
    }

    private fun encryptAndPersist(token: String) {
        val cipher = Cipher.getInstance(TRANSFORMATION)
        cipher.init(Cipher.ENCRYPT_MODE, secretKey())
        val encrypted = cipher.doFinal(token.toByteArray(Charsets.UTF_8))

        check(
            preferences.edit()
                .putString(KEY_CIPHER_TEXT, Base64.encodeToString(encrypted, Base64.NO_WRAP))
                .putString(
                    KEY_INITIALIZATION_VECTOR,
                    Base64.encodeToString(cipher.iv, Base64.NO_WRAP),
                )
                .commit(),
        ) { "The encrypted bearer token could not be persisted." }
    }

    private fun clearLegacySession(): Boolean =
        legacyPreferences.edit().apply {
            LEGACY_SESSION_KEYS.forEach { remove(it) }
        }.commit()

    private fun secretKey(): SecretKey {
        val keyStore = KeyStore.getInstance(KEYSTORE_PROVIDER).apply { load(null) }
        (keyStore.getKey(KEY_ALIAS, null) as? SecretKey)?.let { return it }

        return KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, KEYSTORE_PROVIDER).run {
            init(
                KeyGenParameterSpec.Builder(
                    KEY_ALIAS,
                    KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT,
                )
                    .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                    .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                    .setRandomizedEncryptionRequired(true)
                    .build(),
            )
            generateKey()
        }
    }

    private companion object {
        const val KEYSTORE_PROVIDER = "AndroidKeyStore"
        const val KEY_ALIAS = "educore.mobile.session.v1"
        const val TRANSFORMATION = "AES/GCM/NoPadding"
        const val GCM_TAG_LENGTH_BITS = 128
        const val PREFERENCES_NAME = "educore_secure_session"
        const val KEY_CIPHER_TEXT = "access_token_ciphertext"
        const val KEY_INITIALIZATION_VECTOR = "access_token_iv"
        const val LEGACY_PREFERENCES_NAME = "FlutterSharedPreferences"
        const val LEGACY_TOKEN_KEY = "flutter.token"
        const val LEGACY_UNPREFIXED_TOKEN_KEY = "token"
        val LEGACY_SESSION_KEYS = listOf(
            "flutter.token",
            "flutter.user",
            "flutter.school",
            "flutter.permissions",
            "token",
            "user",
            "school",
            "permissions",
        )
    }
}
