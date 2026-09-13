package online.educoreng.educore.presentation

/**
 * Short-lived handoff used by the published-results UI to request a linked ward
 * without coupling the reusable screen to a ViewModel instance.
 */
internal object PublishedResultWardSelection {
    private var pendingChildId: Long? = null

    fun request(childId: Long) {
        pendingChildId = childId
    }

    fun consume(): Long? = pendingChildId.also { pendingChildId = null }
}
