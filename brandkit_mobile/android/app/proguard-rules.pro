# Flutter specific
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }
-dontwarn io.flutter.embedding.**

# Google Play Services & Ads
-keep class com.google.android.gms.** { *; }
-keep class com.google.ads.** { *; }

# Firebase
-keep class com.google.firebase.** { *; }
-dontwarn com.google.firebase.**

# Razorpay
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}
-keepattributes JavascriptInterface
-keepattributes *Annotation*
-dontwarn com.razorpay.**
-keep class com.razorpay.** { *; }

# OkHttp (used by some plugins)
-dontwarn okhttp3.**
-dontwarn okio.**
-dontwarn javax.annotation.**

# Kotlin
-dontwarn kotlin.**
-keep class kotlin.** { *; }

# General
-keepattributes Signature
-keepattributes *Annotation*
-keep class * extends android.app.Activity

# slf4j fix
-dontwarn org.slf4j.**
