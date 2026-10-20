@echo off
setlocal
echo Building Pixel for local XAMPP over USB.
echo For a LAN APK pass: build_local_apk.bat -LanHost YOUR_PC_ADDRESS
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0brandkit_mobile\scripts\run_local_android.ps1" -BuildApk %*
if errorlevel 1 exit /b 1
copy /Y "%~dp0brandkit_mobile\build\app\outputs\flutter-apk\app-release.apk" "%~dp0app-release.apk"
if errorlevel 1 exit /b 1
echo APK ready. USB builds require adb reverse tcp:8080 tcp:80 while connected.
