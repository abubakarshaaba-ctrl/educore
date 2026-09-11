package online.educoreng.educore.sync

import android.content.Context
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton
import org.json.JSONArray
import org.json.JSONObject
import online.educoreng.educore.core.model.StaffAttendanceCounts
import online.educoreng.educore.core.model.StaffAttendanceRecord
import online.educoreng.educore.core.model.StaffAttendanceSnapshot
import online.educoreng.educore.core.model.StaffAttendanceToday

/**
 * Small device cache for My Attendance.
 *
 * This deliberately stores only the authenticated user's last successful
 * attendance snapshot. It lets staff scan/queue attendance while temporarily
 * offline without pretending stale data is fresh. The server remains the
 * source of truth and WorkManager reconciles queued writes later.
 */
@Singleton
class SelfAttendanceSnapshotCache @Inject constructor(
    @ApplicationContext context: Context,
) {
    private val preferences = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun save(snapshot: StaffAttendanceSnapshot) {
        val root = JSONObject().apply {
            put("month", snapshot.month)
            put("year", snapshot.year)
            put("geo_enabled", snapshot.geoEnabled)
            put("geo_radius_meters", snapshot.geoRadiusMeters)
            put("cached_at", System.currentTimeMillis())
            put("counts", JSONObject().apply {
                put("early", snapshot.counts.early)
                put("present", snapshot.counts.present)
                put("late", snapshot.counts.late)
                put("absent", snapshot.counts.absent)
            })
            snapshot.today?.let { today ->
                put("today", JSONObject().apply {
                    put("status", today.status)
                    putNullable("clock_in", today.clockIn)
                    putNullable("clock_out", today.clockOut)
                })
            }
            put("records", JSONArray().apply {
                snapshot.records.forEach { record ->
                    put(JSONObject().apply {
                        put("date", record.date)
                        put("status", record.status)
                        putNullable("clock_in", record.clockIn)
                        putNullable("clock_out", record.clockOut)
                        putNullable("method", record.method)
                    })
                }
            })
        }
        preferences.edit().putString(KEY_SNAPSHOT, root.toString()).apply()
    }

    fun load(): CachedSelfAttendanceSnapshot? {
        val raw = preferences.getString(KEY_SNAPSHOT, null) ?: return null
        return runCatching {
            val root = JSONObject(raw)
            val countsJson = root.getJSONObject("counts")
            val todayJson = root.optJSONObject("today")
            val recordsJson = root.optJSONArray("records") ?: JSONArray()
            val records = buildList {
                for (index in 0 until recordsJson.length()) {
                    val item = recordsJson.getJSONObject(index)
                    add(
                        StaffAttendanceRecord(
                            date = item.getString("date"),
                            status = item.getString("status"),
                            clockIn = item.optNullableString("clock_in"),
                            clockOut = item.optNullableString("clock_out"),
                            method = item.optNullableString("method"),
                        ),
                    )
                }
            }
            CachedSelfAttendanceSnapshot(
                snapshot = StaffAttendanceSnapshot(
                    month = root.getInt("month"),
                    year = root.getInt("year"),
                    counts = StaffAttendanceCounts(
                        early = countsJson.optInt("early", 0),
                        present = countsJson.optInt("present", 0),
                        late = countsJson.optInt("late", 0),
                        absent = countsJson.optInt("absent", 0),
                    ),
                    today = todayJson?.let {
                        StaffAttendanceToday(
                            status = it.optString("status", "present"),
                            clockIn = it.optNullableString("clock_in"),
                            clockOut = it.optNullableString("clock_out"),
                        )
                    },
                    geoEnabled = root.optBoolean("geo_enabled", false),
                    geoRadiusMeters = root.optInt("geo_radius_meters", 0),
                    records = records,
                ),
                cachedAtEpochMs = root.optLong("cached_at", 0L),
            )
        }.getOrNull()
    }

    fun clear() {
        preferences.edit().remove(KEY_SNAPSHOT).apply()
    }

    private fun JSONObject.putNullable(key: String, value: String?) {
        if (value == null) put(key, JSONObject.NULL) else put(key, value)
    }

    private fun JSONObject.optNullableString(key: String): String? =
        if (!has(key) || isNull(key)) null else optString(key).takeIf { it.isNotBlank() }

    private companion object {
        const val PREFS = "educore_self_attendance_cache"
        const val KEY_SNAPSHOT = "snapshot_v1"
    }
}

data class CachedSelfAttendanceSnapshot(
    val snapshot: StaffAttendanceSnapshot,
    val cachedAtEpochMs: Long,
)
