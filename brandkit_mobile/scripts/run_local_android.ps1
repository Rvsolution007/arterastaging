param(
    [string]$DeviceId = "",
    [switch]$BuildApk,
    [string]$LanHost = ""
)

$ErrorActionPreference = "Stop"
$projectPath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$adbPath = Join-Path $env:LOCALAPPDATA "Android\Sdk\platform-tools\adb.exe"

# The normal local transport is USB, independent of the PC's Wi-Fi IP.
# An unplugged LAN build requires an explicit host for BOTH products.
if ($LanHost) {
    if ([Uri]::CheckHostName($LanHost) -eq [UriHostNameType]::Unknown) {
        throw "LanHost must be a hostname or IPv4 address without a scheme or path."
    }
    $apiOrigin = "http://${LanHost}"
} else {
    $apiOrigin = "http://127.0.0.1:8080"
}

$flutterArguments = @(
    "--dart-define=ENV=local",
    "--dart-define=LOCAL_API_BASE_URL=$apiOrigin/Artera/123456",
    "--dart-define=ADLIVE_LOCAL_API_BASE_URL=$apiOrigin/adlive/public/api/v1"
)

if (-not $BuildApk) {
    # Fail before a long build if XAMPP is unavailable. Do not send credentials.
    Add-Type -AssemblyName System.Net.Http
    $handler = [System.Net.Http.HttpClientHandler]::new()
    $handler.UseProxy = $false
    $client = [System.Net.Http.HttpClient]::new($handler)
    $client.Timeout = [TimeSpan]::FromSeconds(8)
    try {
        foreach ($path in @("/Artera/index.php/healthz", "/adlive/public/login")) {
            $response = $client.GetAsync("http://127.0.0.1$path").GetAwaiter().GetResult()
            if (-not $response.IsSuccessStatusCode) {
                throw "XAMPP returned HTTP $([int]$response.StatusCode) for $path"
            }
            $response.Dispose()
        }
    } catch {
        throw "Start Apache and MySQL in XAMPP, then retry. Both local apps must be reachable."
    } finally {
        $client.Dispose()
        $handler.Dispose()
    }

    if (-not (Test-Path -LiteralPath $adbPath)) {
        throw "Android adb was not found at $adbPath"
    }
    if ([string]::IsNullOrWhiteSpace($DeviceId)) {
        $devices = @((& $adbPath devices) |
            Select-String "\tdevice$" |
            ForEach-Object { ($_.Line -split "\t")[0] })
        if ($devices.Count -ne 1) {
            throw "Connect one authorized Android device, or specify -DeviceId."
        }
        $DeviceId = $devices[0]
    }
    if (-not $LanHost) {
        & $adbPath -s $DeviceId reverse tcp:8080 tcp:80
        if ($LASTEXITCODE -ne 0) {
            throw "Could not configure the Android-to-XAMPP USB bridge."
        }
    }
}

Push-Location $projectPath
try {
    if ($BuildApk) {
        & flutter build apk --release @flutterArguments
    } else {
        & flutter run -d $DeviceId @flutterArguments
    }
    $result = $LASTEXITCODE
} finally {
    Pop-Location
}
exit $result
