# brandkit_mobile

A new Flutter project.

## Local Android run

Use the checked-in runner for a physical Android device connected over USB:

```powershell
.\scripts\run_local_android.ps1
```

It creates an ADB bridge from the phone's `127.0.0.1:8080` to XAMPP port 80
before starting Flutter. This prevents Android from treating the API localhost
as the phone itself. The app also keeps the current XAMPP LAN address as a
direct fallback for a locally built APK.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.
