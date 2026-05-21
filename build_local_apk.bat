@echo off
echo ==============================================
echo Building Local Development APK...
echo Connected to: http://192.168.1.41/Artera
echo ==============================================
cd brandkit_mobile
call flutter build apk --release --dart-define=ENV=local
if %ERRORLEVEL% NEQ 0 (
    echo ==============================================
    echo BUILD FAILED!
    echo ==============================================
    pause
    exit /b %ERRORLEVEL%
)
echo Copying APK to root directory...
copy /Y build\app\outputs\flutter-apk\app-release.apk ..\app-release.apk
echo ==============================================
echo BUILD SUCCESSFUL!
echo APK is available in the root folder as 'app-release.apk'
echo ==============================================
pause
