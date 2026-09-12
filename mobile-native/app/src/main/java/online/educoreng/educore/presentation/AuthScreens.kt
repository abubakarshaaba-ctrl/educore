package online.educoreng.educore.presentation

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Security
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
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
                BrandPanel(Modifier.weight(0.46f).fillMaxHeight(), expanded = true)
                Box(
                    modifier = Modifier
                        .weight(0.54f)
                        .fillMaxHeight()
                        .background(Brush.verticalGradient(listOf(Color.White, EduCoreColors.Page50)))
                        .imePadding()
                        .padding(40.dp),
                    contentAlignment = Alignment.Center,
                ) {
                    AuthCard(
                        state = state,
                        onLogin = onLogin,
                        onForgotPassword = onForgotPassword,
                        onRequestReset = onRequestReset,
                        onBackToLogin = onBackToLogin,
                        modifier = Modifier.widthIn(max = 520.dp),
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
                BrandPanel(
                    modifier = Modifier.fillMaxWidth().height(292.dp),
                    expanded = false,
                )
                AuthCard(
                    state = state,
                    onLogin = onLogin,
                    onForgotPassword = onForgotPassword,
                    onRequestReset = onRequestReset,
                    onBackToLogin = onBackToLogin,
                    modifier = Modifier
                        .padding(horizontal = EduCoreSpacing.Lg)
                        .offset(y = (-36).dp)
                        .widthIn(max = 480.dp),
                )
                SecureAccessFooter(Modifier.offset(y = (-18).dp))
            }
        }
    }
}

@Composable
private fun BrandPanel(modifier: Modifier, expanded: Boolean) {
    Box(
        modifier = modifier.background(
            Brush.linearGradient(
                colors = listOf(Color(0xFF061936), EduCoreColors.Navy900, EduCoreColors.Navy800),
                start = Offset.Zero,
                end = Offset(1100f, 1800f),
            ),
        ),
    ) {
        BrandBackdrop(Modifier.fillMaxSize())
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(
                    horizontal = if (expanded) 56.dp else 24.dp,
                    vertical = if (expanded) 64.dp else 30.dp,
                ),
            horizontalAlignment = if (expanded) Alignment.Start else Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            androidx.compose.foundation.Image(
                painter = painterResource(R.drawable.ic_educore_mark),
                contentDescription = "EduCore",
                modifier = Modifier.size(if (expanded) 86.dp else 66.dp),
            )
            Spacer(Modifier.height(if (expanded) EduCoreSpacing.Xl else EduCoreSpacing.Md))
            Row(verticalAlignment = Alignment.Bottom) {
                val wordmarkStyle = MaterialTheme.typography.displaySmall.copy(
                    fontSize = if (expanded) 34.sp else 30.sp,
                    lineHeight = if (expanded) 40.sp else 36.sp,
                    fontWeight = FontWeight.SemiBold,
                )
                Text("Edu", color = Color.White, style = wordmarkStyle)
                Text("Core", color = EduCoreColors.Gold400, style = wordmarkStyle)
                Spacer(Modifier.width(EduCoreSpacing.Xs))
                Text(
                    "ERP",
                    color = EduCoreColors.Gold400,
                    style = MaterialTheme.typography.labelLarge,
                    modifier = Modifier.padding(bottom = 5.dp),
                )
            }
            Spacer(Modifier.height(EduCoreSpacing.Sm))
            Text(
                text = if (expanded) "One platform for every school day." else "Your school. Connected.",
                color = Color.White.copy(alpha = 0.82f),
                style = if (expanded) MaterialTheme.typography.titleLarge else MaterialTheme.typography.bodyLarge,
                textAlign = if (expanded) TextAlign.Start else TextAlign.Center,
            )
            if (expanded) {
                Spacer(Modifier.height(EduCoreSpacing.Xxl))
                Text(
                    text = "Teach, manage and make better decisions from one secure workspace.",
                    color = Color.White.copy(alpha = 0.72f),
                    style = MaterialTheme.typography.bodyLarge,
                    modifier = Modifier.widthIn(max = 390.dp),
                )
                Spacer(Modifier.height(EduCoreSpacing.Xxl))
                BrandFeature(Icons.Default.School, "Academics and school operations")
                Spacer(Modifier.height(EduCoreSpacing.Md))
                BrandFeature(Icons.Default.CheckCircle, "Role-based access and live records")
            }
        }
    }
}

@Composable
private fun BrandBackdrop(modifier: Modifier = Modifier) {
    Canvas(modifier) {
        drawCircle(
            color = Color.White.copy(alpha = 0.045f),
            radius = size.minDimension * 0.42f,
            center = Offset(size.width * 0.88f, size.height * 0.12f),
        )
        drawCircle(
            color = EduCoreColors.Gold400.copy(alpha = 0.16f),
            radius = size.minDimension * 0.28f,
            center = Offset(size.width * 0.08f, size.height * 0.92f),
            style = Stroke(width = 2.dp.toPx()),
        )
        drawCircle(
            color = Color.White.copy(alpha = 0.07f),
            radius = size.minDimension * 0.13f,
            center = Offset(size.width * 0.78f, size.height * 0.78f),
            style = Stroke(width = 1.dp.toPx()),
        )
    }
}

@Composable
private fun BrandFeature(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String) {
    Row(
        modifier = Modifier
            .clip(RoundedCornerShape(14.dp))
            .background(Color.White.copy(alpha = 0.07f))
            .border(0.6.dp, Color.White.copy(alpha = 0.09f), RoundedCornerShape(14.dp))
            .padding(horizontal = EduCoreSpacing.Lg, vertical = EduCoreSpacing.Md),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Icon(icon, contentDescription = null, tint = EduCoreColors.Gold400, modifier = Modifier.size(20.dp))
        Spacer(Modifier.width(EduCoreSpacing.Md))
        Text(label, color = Color.White.copy(alpha = 0.90f), style = MaterialTheme.typography.bodyMedium)
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
        shape = RoundedCornerShape(24.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = BorderStroke(0.75.dp, EduCoreColors.Line200),
        elevation = CardDefaults.cardElevation(defaultElevation = 6.dp),
    ) {
        BoxWithConstraints {
            val contentPadding = if (maxWidth < 380.dp) EduCoreSpacing.Xl else EduCoreSpacing.Xxl
            Column(Modifier.padding(contentPadding)) {
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
}

@Composable
private fun LoginForm(state: AppUiState, onLogin: (String, String) -> Unit, onForgotPassword: () -> Unit) {
    var loginId by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }

    AuthEyebrow("SECURE SIGN IN")
    Spacer(Modifier.height(EduCoreSpacing.Sm))
    Text("Welcome back", style = MaterialTheme.typography.headlineSmall)
    Text(
        "Use the account provided by your school.",
        style = MaterialTheme.typography.bodyMedium,
        color = EduCoreColors.Slate600,
    )
    Spacer(Modifier.height(EduCoreSpacing.Xxl))
    EduCoreTextField(
        value = loginId,
        onValueChange = { loginId = it },
        label = "Login ID",
        modifier = Modifier.fillMaxWidth(),
        enabled = !state.isBusy,
        supportingText = "Email, staff ID or admission number",
        error = state.fieldErrors["login_id"],
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text, imeAction = ImeAction.Next),
        leadingIcon = {
            Icon(Icons.Default.Person, contentDescription = null, tint = EduCoreColors.Navy700)
        },
    )
    Spacer(Modifier.height(EduCoreSpacing.Sm))
    EduCoreTextField(
        value = password,
        onValueChange = { password = it },
        label = "Password",
        modifier = Modifier.fillMaxWidth(),
        enabled = !state.isBusy,
        error = state.fieldErrors["password"],
        visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password, imeAction = ImeAction.Done),
        keyboardActions = KeyboardActions(onDone = { onLogin(loginId, password) }),
        leadingIcon = {
            Icon(Icons.Default.Lock, contentDescription = null, tint = EduCoreColors.Navy700)
        },
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
    Spacer(Modifier.height(EduCoreSpacing.Xs))
    EduCorePrimaryButton(
        text = "Sign in securely",
        onClick = { onLogin(loginId, password) },
        modifier = Modifier.fillMaxWidth(),
        enabled = state.isOnline && loginId.isNotBlank() && password.isNotBlank(),
        loading = state.isBusy,
        leadingIcon = {
            Icon(Icons.Default.Security, contentDescription = null, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(EduCoreSpacing.Sm))
        },
    )
    Spacer(Modifier.height(EduCoreSpacing.Lg))
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.Center,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Icon(
            Icons.Default.Lock,
            contentDescription = null,
            tint = EduCoreColors.Success600,
            modifier = Modifier.size(15.dp),
        )
        Spacer(Modifier.width(EduCoreSpacing.Sm))
        Text(
            "Protected institutional access",
            style = MaterialTheme.typography.bodySmall,
            color = EduCoreColors.Muted500,
        )
    }
}

@Composable
private fun AuthEyebrow(text: String) {
    Text(
        text,
        color = EduCoreColors.Gold700,
        style = MaterialTheme.typography.labelMedium.copy(
            fontWeight = FontWeight.SemiBold,
            letterSpacing = 1.0.sp,
        ),
    )
}

@Composable
private fun ForgotPasswordForm(
    state: AppUiState,
    onRequestReset: (String) -> Unit,
    onBackToLogin: () -> Unit,
) {
    var email by remember { mutableStateOf("") }

    AuthEyebrow("ACCOUNT RECOVERY")
    Spacer(Modifier.height(EduCoreSpacing.Sm))
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
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email, imeAction = ImeAction.Done),
        keyboardActions = KeyboardActions(onDone = { onRequestReset(email) }),
        leadingIcon = {
            Icon(Icons.Default.Email, contentDescription = null, tint = EduCoreColors.Navy700)
        },
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
private fun SecureAccessFooter(modifier: Modifier = Modifier) {
    Row(
        modifier = modifier.padding(horizontal = EduCoreSpacing.Xl).heightIn(min = EduCoreSizes.TouchTarget),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.Center,
    ) {
        Surface(shape = CircleShape, color = EduCoreColors.Success100) {
            Icon(
                Icons.Default.Security,
                contentDescription = null,
                tint = EduCoreColors.Success700,
                modifier = Modifier.padding(EduCoreSpacing.Sm).size(16.dp),
            )
        }
        Spacer(Modifier.width(EduCoreSpacing.Md))
        Column {
            Text("Secure access", style = MaterialTheme.typography.labelLarge, color = EduCoreColors.Navy900)
            Text(
                "Encrypted connection to EduCore",
                style = MaterialTheme.typography.bodySmall,
                color = EduCoreColors.Muted500,
            )
        }
    }
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
                    Icon(
                        Icons.Default.Lock,
                        contentDescription = null,
                        modifier = Modifier.padding(EduCoreSpacing.Lg).size(EduCoreSizes.LargeIcon),
                    )
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
