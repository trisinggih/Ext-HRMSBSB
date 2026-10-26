package com.tpsmedia.bsbhrms

import android.Manifest
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.util.Log
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.addCallback
import androidx.core.app.ActivityCompat
import com.jangeby.system.App
import com.jangeby.system.notification.FcmManager
import com.jangeby.system.security.SecurityChecker
import com.jangeby.system.security.SecurityViolation
import kotlin.system.exitProcess

class MainActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Check security state immediately
        SecurityChecker.checkSecurity()

        // Handle back press: if security violation active, exit app immediately
        onBackPressedDispatcher.addCallback(this) {
            if (SecurityChecker.violationState.value !is SecurityViolation.None) {
                finishAffinity()
                exitProcess(0)
            } else {
                isEnabled = false
                onBackPressedDispatcher.onBackPressed()
                isEnabled = true
            }
        }

        // Request runtime permissions for notifications & location
        requestPermissionsIfNeeded()

        // Sync FCM token with backend
        FcmManager.syncToken()

        setContent {
            App() // Dari shared composeApp
        }
    }

    override fun onResume() {
        super.onResume()
        // Re-check security status when resuming activity
        SecurityChecker.checkSecurity()
    }

    private fun requestPermissionsIfNeeded() {
        val permissions = mutableListOf<String>()

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                permissions.add(Manifest.permission.POST_NOTIFICATIONS)
            }
        }

        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            permissions.add(Manifest.permission.ACCESS_FINE_LOCATION)
        }
        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            permissions.add(Manifest.permission.ACCESS_COARSE_LOCATION)
        }

        if (permissions.isNotEmpty()) {
            ActivityCompat.requestPermissions(this, permissions.toTypedArray(), 1001)
        }
    }
}
