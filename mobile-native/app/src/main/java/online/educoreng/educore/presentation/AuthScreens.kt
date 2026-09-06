package online.educoreng.educore.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import online.educoreng.educore.R
import online.educoreng.educore.core.designsystem.component.EduCoreInfoBanner
import online.educoreng.educore.core.designsystem.component.EduCoreOfflineBanner
import online.educoreng.educore.core.designsystem.component.EduCorePrimaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreSecondaryButton
import online.educoreng.educore.core.designsystem.component.EduCoreTextButton
import online.educoreng.educore.core.designsystem.component.EduCoreTextField
import online.educoreng.educore.core.designsystem.layout.EduCoreAdaptiveLayout
import online.educoreng.educore.core.designsystem.layout.EduCoreWindowWidth
import online.educoreng.educore.core.designsystem.theme.EduCoreColors
import online.educoreng.educore.core.designsystem.theme.EduCoreElevation
import online.educoreng.educore.core.designsystem.theme.EduCoreSizes
import online.educoreng.educore.core.designsystem.theme.EduCoreSpacing
import online.educoreng.educore.core.model.SessionSnapshot

@Composable
internal fun StartupScreen() {
    Column(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Navy900),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        androidx.compose.foundation.Image(
            painter = painterResource(R.drawable.ic_educore_mark),
            contentDescription = null,
            modifier = Modifier.size(82.dp),
        )
        Spacer(Modifier.height(EduCoreSpacing.Lg))
        Text(
            text = "EduCore",
            color = Color.White,
            style = MaterialTheme.typography.displaySmall,
        )
        Text(
            text = "School ERP",
            color = EduCoreColors.Gold400,
            style = MaterialTheme.typography.labelLarge,
        )
        Spacer(Modifier.height(EduCoreSpacing.Xxl))
        CircularProgressIndicator(color = EduCoreColors.Gold400, strokeWidth = 3.dp)
        Spacer(Modifier.height(EduCoreSpacing.Md))
        Text(
            text = "Preparing your secure workspace",
            color = Color.White.copy(alpha = 0.76f),
            style = MaterialTheme.typography.bodySmall,
        )
    }
}

@Composable
internal fun AuthenticationScreen(
    state: AppUiState,
    onLogin: (String, String) -> Unit,
    onForgotPassword: () -> Unit,
    onRequestReset: (String) -> Unit,
    onBackToLogin: () -> Unit,
) {
    EduCoreAdaptiveLayout(Modifier.fillMaxSize()) { width ->
        if (width == EduCoreWindowWidth.Expanded) {
            Row(Modifier.fillMaxSize().background(EduCoreColors.Page50)) {
                BrandPanel(Modifier.weight(0.44f).fillMaxHeight(), expanded = true)
                Box(
                    modifier = Modifier.weight(0.56f).fillMaxHeight().imePadding().padding(EduCoreSpacing.Xxl),
                    contentAlignment = Alignment.Center,
                ) {
                    AuthCard(
                        state = state,
                        onLogin = onLogin,
                        onForgotPassword = onForgotPassword,
                        onRequestReset = onRequestReset,
                        onBackToLogin = onBackToLogin,
                    )
                }
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .background(EduCoreColors.Page50)
                    .imePadding()
                    .verticalScroll(rememberScrollState()),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                BrandPanel(Modifier.fillMaxWidth(), expanded = false)
                AuthCard(
                    state = state,
                    onLogin = onLogin,
                    onForgotPassword = onForgotPassword,
                    onRequestReset = onRequestReset,
                    onBackToLogin = onBackToLogin,
                    modifier = Modifier.padding(EduCoreSpacing.Lg).widthIn(max = 480.dp),
                )
            }
        }
    }
}

@Composable
private fun BrandPanel(
    modifier: Modifier,
    expanded: Boolean,
) {
    Column(
        modifier = modifier
            .background(EduCoreColors.Navy900)
            .padding(horizontal = if (expanded) 52.dp else 28.dp, vertical = if (expanded) 64.dp else 38.dp),
        horizontalAlignment = if (expanded) Alignment.Start else Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        androidx.compose.foundation.Image(
            painter = painterResource(R.drawable.ic_educore_mark),
            contentDescription = "EduCore",
            modifier = Modifier.size(if (expanded) 94.dp else 68.dp),
        )
        Spacer(Modifier.height(EduCoreSpacing.Lg))
        Text("EduCore", color = Color.White, style = MaterialTheme.typography.displaySmall)
        Text("School ERP", color = EduCoreColors.Gold400, style = MaterialTheme.typography.titleSmall)
        if (expanded) {
            Spacer(Modifier.height(EduCoreSpacing.Xl))
            Text(
                text = "One secure workspace for teaching, learning and school operations.",
                color = Color.White.copy(alpha = 0.78f),
                style = MaterialTheme.typography.bodyLarge,
                modifier = Modifier.widthIn(max = 360.dp),
            )
        }
    }
}

@Composable
private fun AuthCard(
    state: AppUiState,
    onLogin: (String, String) -> Unit,
    onForgotPassword: () -> Unit,
    onRequestReset: (String) -> Unit,
    onBackToLogin: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = EduCoreElevation.Raised),
    ) {
        Column(Modifier.padding(EduCoreSpacing.Xxl)) {
            if (!state.isOnline) {
                EduCoreOfflineBanner(message = "Connect to the internet to sign in.")
                Spacer(Modifier.height(EduCoreSpacing.Lg))
            }
            when (state.authMode) {
                AuthMode.LOGIN -> LoginForm(state, onLogin, onForgotPassword)
                AuthMode.FORGOT_PASSWORD -> ForgotPasswordForm(state, onRequestReset, onBackToLogin)
            }
        }
    }
}

@Composable
private fun LoginForm(
    state: AppUiState,
    onLogin: (String, String) -> Unit,
    onForgotPassword: () -> Unit,
) {
    var loginId by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }

    Text("Welcome back", style = MaterialTheme.typography.headlineSmall)
    Text(
        "Sign in to your existing EduCore account.",
        style = MaterialTheme.typography.bodyMedium,
        color = EduCoreColors.Slate600,
    )
    Spacer(Modifier.height(EduCoreSpacing.Xl))
    EduCoreTextField(
        value = loginId,
        onValueChange = { loginId = it },
        label = "Email, staff ID or admission number",
        modifier = Modifier.fillMaxWidth(),
        enabled = !state.isBusy,
        error = state.fieldErrors["login_id"],
        keyboardOptions = KeyboardOptions(
            keyboardType = KeyboardType.Text,
            imeAction = ImeAction.Next,
        ),
    )
    Spacer(Modifier.height(EduCoreSpacing.Md))
    EduCoreTextField(
        value = password,
        onValueChange = { password = it },
        label = "Password",
        modifier = Modifier.fillMaxWidth(),
        enabled = !state.isBusy,
        error = state.fieldErrors["password"],
        visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
        keyboardOptions = KeyboardOptions(
            keyboardType = KeyboardType.Password,
            imeAction = ImeAction.Done,
        ),
        keyboardActions = KeyboardActions(onDone = { onLogin(loginId, password) }),
        trailingIcon = {
            IconButton(onClick = { passwordVisible = !passwordVisible }) {
                Icon(
                    if (passwordVisible) Icons.Default.VisibilityOff else Icons.Default.Visibility,
                    contentDescription = if (passwordVisible) "Hide password" else "Show password",
                )
            }
        },
    )
    Box(Modifier.fillMaxWidth(), contentAlignment = Alignment.CenterEnd) {
        EduCoreTextButton("Forgot password?", onForgotPassword, enabled = !state.isBusy)
    }
    Spacer(Modifier.height(EduCoreSpacing.Sm))
    EduCorePrimaryButton(
        text = "Sign in",
        onClick = { onLogin(loginId, password) },
        modifier = Modifier.fillMaxWidth(),
        enabled = state.isOnline && loginId.isNotBlank() && password.isNotBlank(),
        loading = state.isBusy,
    )
    Spacer(Modifier.height(EduCoreSpacing.Lg))
    Text(
        text = "Your school, role and permitted modules are resolved securely after sign-in.",
        modifier = Modifier.fillMaxWidth(),
        style = MaterialTheme.typography.bodySmall,
        color = EduCoreColors.Muted500,
        textAlign = TextAlign.Center,
    )
}

@Composable
private fun ForgotPasswordForm(
    state: AppUiState,
    onRequestReset: (String) -> Unit,
    onBackToLogin: () -> Unit,
) {
    var email by remember { mutableStateOf("") }
    Row(verticalAlignment = Alignment.CenterVertically) {
        IconButton(onClick = onBackToLogin, enabled = !state.isBusy) {
            Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back to sign in")
        }
        Text("Reset password", style = MaterialTheme.typography.headlineSmall)
    }
    Spacer(Modifier.height(EduCoreSpacing.Sm))
    EduCoreInfoBanner("Enter the email address registered with your EduCore account.")
    Spacer(Modifier.height(EduCoreSpacing.Xl))
    EduCoreTextField(
        value = email,
        onValueChange = { email = it },
        label = "Email address",
        modifier = Modifier.fillMaxWidth(),
        enabled = !state.isBusy,
        error = state.fieldErrors["email"],
        keyboardOptions = KeyboardOptions(
            keyboardType = KeyboardType.Email,
            imeAction = ImeAction.Done,
        ),
        keyboardActions = KeyboardActions(onDone = { onRequestReset(email) }),
    )
    Spacer(Modifier.height(EduCoreSpacing.Xl))
    EduCorePrimaryButton(
        text = "Send reset link",
        onClick = { onRequestReset(email) },
        modifier = Modifier.fillMaxWidth(),
        enabled = state.isOnline && email.isNotBlank(),
        loading = state.isBusy,
    )
    EduCoreTextButton(
        text = "Back to sign in",
        onClick = onBackToLogin,
        modifier = Modifier.fillMaxWidth(),
        enabled = !state.isBusy,
    )
}

@Composable
internal fun AccessBlockedScreen(
    session: SessionSnapshot,
    busy: Boolean,
    online: Boolean,
    onRetry: () -> Unit,
    onLogout: () -> Unit,
) {
    Box(
        modifier = Modifier.fillMaxSize().background(EduCoreColors.Page50).padding(EduCoreSpacing.Xxl),
        contentAlignment = Alignment.Center,
    ) {
        Card(
            modifier = Modifier.fillMaxWidth().widthIn(max = 520.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(defaultElevation = EduCoreElevation.Raised),
        ) {
            Column(
                modifier = Modifier.padding(EduCoreSpacing.Xxl),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Surface(
                    shape = MaterialTheme.shapes.large,
                    color = EduCoreColors.Warning100,
                    contentColor = EduCoreColors.Warning700,
                ) {
                    Icon(Icons.Default.Lock, contentDescription = null, modifier = Modifier.padding(EduCoreSpacing.Lg).size(EduCoreSizes.LargeIcon))
                }
                Spacer(Modifier.height(EduCoreSpacing.Lg))
                Text(session.school.name, style = MaterialTheme.typography.titleLarge, textAlign = TextAlign.Center)
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                Text(session.access.message, textAlign = TextAlign.Center, color = EduCoreColors.Slate600)
                Spacer(Modifier.height(EduCoreSpacing.Xl))
                EduCorePrimaryButton(
                    text = "Check again",
                    onClick = onRetry,
                    modifier = Modifier.fillMaxWidth(),
                    enabled = online,
                    loading = busy,
                    leadingIcon = { Icon(Icons.Default.Refresh, contentDescription = null) },
                )
                Spacer(Modifier.height(EduCoreSpacing.Sm))
                EduCoreSecondaryButton("Sign out", onLogout, Modifier.fillMaxWidth(), enabled = !busy)
            }
        }
    }
}
