@echo off
echo ==============================================
echo Building Staging App Bundle (AAB)...
echo Connected to: https://staging.arterapixel.com
echo ==============================================
cd brandkit_mobile
call flutter build appbundle --release --dart-define=ENV=staging
if %ERRORLEVEL% NEQ 0 (
    echo ==============================================
    echo BUILD FAILED!
    echo ==============================================
    pause
    exit /b %ERRORLEVEL%
)
echo Copying AAB to root directory...
copy /Y build\app\outputs\bundle\release\app-release.aab ..\app-staging-release.aab
echo ==============================================
echo BUILD SUCCESSFUL!
echo AAB is available in the root folder as 'app-staging-release.aab'
echo ==============================================
pause
