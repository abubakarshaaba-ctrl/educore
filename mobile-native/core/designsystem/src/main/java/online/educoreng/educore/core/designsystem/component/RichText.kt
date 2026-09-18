package online.educoreng.educore.core.designsystem.component

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.TextLayoutResult
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.TextUnit

/**
 * Renders the same small Markdown subset used by EduCore web broadcasts/messages.
 *
 * Supported blocks: # H1, ## H2, ### H3, bullets and numbered lists.
 * Supported inline formatting: **bold**, *italic*, [link label](url).
 *
 * Existing plain-text messages render exactly as ordinary body text.
 */
@Composable
fun EduCoreRichText(
    markdown: String,
    modifier: Modifier = Modifier,
    color: Color = Color.Unspecified,
    maxLines: Int = Int.MAX_VALUE,
    overflow: TextOverflow = TextOverflow.Clip,
    onTextLayout: (TextLayoutResult) -> Unit = {},
) {
    val typography = MaterialTheme.typography
    val annotated = buildRichText(
        markdown = markdown,
        h1Size = typography.headlineSmall.fontSize,
        h2Size = typography.titleLarge.fontSize,
        h3Size = typography.titleMedium.fontSize,
    )

    Text(
        text = annotated,
        modifier = modifier,
        style = typography.bodyMedium,
        color = color,
        maxLines = maxLines,
        overflow = overflow,
        onTextLayout = onTextLayout,
    )
}

fun eduCoreRichTextPlainText(markdown: String): String {
    return markdown
        .lineSequence()
        .map { line ->
            line
                .replace(Regex("^\\s*#{1,3}\\s+"), "")
                .replace(Regex("^\\s*[-*]\\s+"), "• ")
                .replace(Regex("^\\s*\\d+[.)]\\s+"), "")
                .replace(Regex("\\*\\*([^*\\n]+)\\*\\*"), "$1")
                .replace(Regex("(?<!\\*)\\*([^*\\n]+)\\*(?!\\*)"), "$1")
                .replace(Regex("\\[([^]\\n]+)]\\(([^\\s)]+)\\)"), "$1")
        }
        .joinToString("\n")
}

private fun buildRichText(
    markdown: String,
    h1Size: TextUnit,
    h2Size: TextUnit,
    h3Size: TextUnit,
): AnnotatedString = buildAnnotatedString {
    val lines = markdown.lines()

    lines.forEachIndexed { index, original ->
        val trimmedStart = original.trimStart()
        val (content, blockStyle, prefix) = when {
            trimmedStart.startsWith("### ") -> Triple(
                trimmedStart.removePrefix("### "),
                SpanStyle(fontWeight = FontWeight.Bold, fontSize = h3Size),
                "",
            )
            trimmedStart.startsWith("## ") -> Triple(
                trimmedStart.removePrefix("## "),
                SpanStyle(fontWeight = FontWeight.ExtraBold, fontSize = h2Size),
                "",
            )
            trimmedStart.startsWith("# ") -> Triple(
                trimmedStart.removePrefix("# "),
                SpanStyle(fontWeight = FontWeight.ExtraBold, fontSize = h1Size),
                "",
            )
            Regex("^[-*]\\s+").containsMatchIn(trimmedStart) -> Triple(
                trimmedStart.replaceFirst(Regex("^[-*]\\s+"), ""),
                SpanStyle(),
                "• ",
            )
            else -> Triple(original, SpanStyle(), "")
        }

        if (prefix.isNotEmpty()) append(prefix)

        if (blockStyle != SpanStyle()) {
            pushStyle(blockStyle)
            appendInline(content)
            pop()
        } else {
            appendInline(content)
        }

        if (index != lines.lastIndex) append("\n")
    }
}

private fun AnnotatedString.Builder.appendInline(text: String) {
    var cursor = 0

    while (cursor < text.length) {
        if (text.startsWith("**", cursor)) {
            val end = text.indexOf("**", cursor + 2)
            if (end > cursor + 2) {
                pushStyle(SpanStyle(fontWeight = FontWeight.Bold))
                append(text.substring(cursor + 2, end))
                pop()
                cursor = end + 2
                continue
            }
        }

        if (text[cursor] == '*') {
            val end = text.indexOf('*', cursor + 1)
            if (end > cursor + 1) {
                pushStyle(SpanStyle(fontStyle = FontStyle.Italic))
                append(text.substring(cursor + 1, end))
                pop()
                cursor = end + 1
                continue
            }
        }

        if (text[cursor] == '[') {
            val labelEnd = text.indexOf(']', cursor + 1)
            val openUrl = if (labelEnd >= 0 && labelEnd + 1 < text.length && text[labelEnd + 1] == '(') labelEnd + 1 else -1
            val closeUrl = if (openUrl >= 0) text.indexOf(')', openUrl + 1) else -1
            if (labelEnd > cursor && closeUrl > openUrl + 1) {
                val label = text.substring(cursor + 1, labelEnd)
                val url = text.substring(openUrl + 1, closeUrl)
                pushStringAnnotation(tag = "URL", annotation = url)
                pushStyle(SpanStyle(textDecoration = TextDecoration.Underline, fontWeight = FontWeight.Medium))
                append(label)
                pop()
                pop()
                cursor = closeUrl + 1
                continue
            }
        }

        append(text[cursor])
        cursor++
    }
}
