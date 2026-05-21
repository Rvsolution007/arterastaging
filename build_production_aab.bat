@echo off
echo ==============================================
echo Building Production App Bundle (AAB)...
echo Connected to: https://arterapixel.com
echo ==============================================
cd brandkit_mobile
call flutter build appbundle --release --dart-define=ENV=production
if %ERRORLEVEL% NEQ 0 (
    echo ==============================================
    echo BUILD FAILED!
    echo ==============================================
    pause
    exit /b %ERRORLEVEL%
)
echo Copying AAB to root directory...
copy /Y build\app\outputs\bundle\release\app-release.aab ..\app-release.aab
echo ==============================================
echo BUILD SUCCESSFUL!
echo AAB is available in the root folder as 'app-release.aab'
echo ==============================================
pause
