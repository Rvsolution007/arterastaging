package com.arterapixel.pro

import com.facebook.FacebookSdk
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    companion object {
        private const val FACEBOOK_SDK_CHANNEL = "com.arterapixel.pro/facebook_sdk"
    }

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, FACEBOOK_SDK_CHANNEL)
            .setMethodCallHandler { call, result ->
                if (call.method != "setClientToken") {
                    result.notImplemented()
                    return@setMethodCallHandler
                }

                val clientToken = call.argument<String>("clientToken")?.trim()
                if (clientToken.isNullOrEmpty()) {
                    result.error(
                        "missing_client_token",
                        "Facebook Client Token is missing from the AdLive configuration.",
                        null,
                    )
                    return@setMethodCallHandler
                }

                try {
                    // The Client Token is an Android SDK identifier (not the
                    // app secret). It is fetched only after AdLive mobile SSO
                    // so an admin can rotate it without embedding it in an APK.
                    FacebookSdk.setClientToken(clientToken)
                    result.success(null)
                } catch (exception: Exception) {
                    result.error(
                        "facebook_sdk_configuration_failed",
                        "Facebook app login could not be configured.",
                        null,
                    )
                }
            }
    }
}
