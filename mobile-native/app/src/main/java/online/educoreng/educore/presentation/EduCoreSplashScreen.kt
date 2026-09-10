package online.educoreng.educore.presentation

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import online.educoreng.educore.R
import online.educoreng.educore.core.designsystem.theme.EduCoreColors

@Composable
internal fun EduCoreSplashScreen() {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(EduCoreColors.Navy900),
    ) {
        Canvas(Modifier.fillMaxSize()) {
            val min = size.minDimension
            drawCircle(
                color = Color.White.copy(alpha = 0.045f),
                radius = min * 0.46f,
                center = Offset(size.width * 0.92f, size.height * 0.08f),
            )
            drawCircle(
                color = EduCoreColors.Gold400.copy(alpha = 0.12f),
                radius = min * 0.28f,
                center = Offset(size.width * 0.04f, size.height * 0.92f),
                style = Stroke(width = 1.5.dp.toPx()),
            )
            drawCircle(
                color = Color.White.copy(alpha = 0.055f),
                radius = min * 0.14f,
                center = Offset(size.width * 0.82f, size.height * 0.78f),
                style = Stroke(width = 1.dp.toPx()),
            )
        }

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 36.dp, vertical = 44.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            Surface(
                modifier = Modifier.size(96.dp),
                shape = RoundedCornerShape(24.dp),
                color = Color.White.copy(alpha = 0.07f),
                shadowElevation = 2.dp,
            ) {
                Image(
                    painter = painterResource(R.drawable.ic_educore_mark),
                    contentDescription = "EduCore",
                    modifier = Modifier.padding(8.dp),
                )
            }

            Spacer(Modifier.height(24.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    text = "Edu",
                    color = Color.White,
                    style = MaterialTheme.typography.headlineLarge,
                )
                Text(
                    text = "Core",
                    color = EduCoreColors.Gold400,
                    style = MaterialTheme.typography.headlineLarge,
                )
            }
            Spacer(Modifier.height(6.dp))
            Text(
                text = "Your school. Connected.",
                color = Color.White.copy(alpha = 0.78f),
                style = MaterialTheme.typography.bodyMedium,
                textAlign = TextAlign.Center,
            )

            Spacer(Modifier.height(56.dp))
            LinearProgressIndicator(
                modifier = Modifier
                    .fillMaxWidth(0.42f)
                    .height(3.dp),
                color = EduCoreColors.Gold400,
                trackColor = Color.White.copy(alpha = 0.16f),
            )
            Spacer(Modifier.height(14.dp))
            Text(
                text = "Loading your workspace",
                color = Color.White.copy(alpha = 0.58f),
                style = MaterialTheme.typography.labelMedium,
            )
        }

        Text(
            text = "Simple • Secure • Connected",
            color = Color.White.copy(alpha = 0.46f),
            style = MaterialTheme.typography.labelSmall,
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .padding(bottom = 28.dp),
        )
    }
}
