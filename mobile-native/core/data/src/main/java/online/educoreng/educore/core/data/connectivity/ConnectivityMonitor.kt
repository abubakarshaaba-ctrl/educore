package online.educoreng.educore.core.data.connectivity

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import kotlinx.coroutines.flow.distinctUntilChanged

interface ConnectivityMonitor {
    val isOnline: Flow<Boolean>
}

class AndroidConnectivityMonitor(context: Context) : ConnectivityMonitor {
    private val manager = context.getSystemService(ConnectivityManager::class.java)

    override val isOnline: Flow<Boolean> = callbackFlow {
        fun currentStatus(): Boolean {
            val active = manager.activeNetwork ?: return false
            val capabilities = manager.getNetworkCapabilities(active) ?: return false
            return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
                capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
        }

        val callback = object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) {
                trySend(currentStatus())
            }

            override fun onLost(network: Network) {
                trySend(currentStatus())
            }

            override fun onCapabilitiesChanged(network: Network, capabilities: NetworkCapabilities) {
                trySend(currentStatus())
            }
        }

        trySend(currentStatus())
        manager.registerNetworkCallback(NetworkRequest.Builder().build(), callback)
        awaitClose { manager.unregisterNetworkCallback(callback) }
    }.distinctUntilChanged()
}
