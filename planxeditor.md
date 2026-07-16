# 🏗️ planxeditor.md — Complete Implementation Blueprint (Code-Level)

**File:** `planxeditor.md`  
**Created:** 14 July 2026  
**Purpose:** This is the ULTIMATE implementation guide. Every single requirement from `nativexwebxeditor.md` is broken down into **exact code snippets, database migrations, file paths, function signatures, and step-by-step instructions** so that ANY developer or AI can implement it 100% perfectly without ambiguity.

**Reference Architecture Doc:** `nativexwebxeditor.md`  
**Workspace Rules:** `.agents/AGENTS.md` (ALL locked code sections require password `Brijesh@1415`)

---

# 📋 TABLE OF CONTENTS

1. [STEP 1: Direct API JSON Serving (HomeApi.php)](#step-1)
2. [STEP 2: Mobile App Hybrid Renderer & Asset Caching (Flutter)](#step-2)
3. [STEP 3: Redis Cache + ETag 304 Smart Sync (Laravel)](#step-3)
4. [STEP 4: Bundled Batch API](#step-4)
5. [STEP 5: Golden Snapshot Baseline System (Database + Capture)](#step-5)
6. [STEP 6: Web Editor One-Click Diff Review Modal](#step-6)
7. [STEP 7: Version Dashboard Dual Engine Validation](#step-7)
8. [STEP 8: Structured Mismatch Review Popup (Blade UI)](#step-8)
9. [STEP 9: Auto-Compensate Back-Calculation Engine](#step-9)
10. [STEP 10: Regression Test Admin Pages](#step-10)
11. [STEP 11: Benchmark Control Frames System](#step-11)

---
---

# STEP 1: Direct API JSON Serving (HomeApi.php) {#step-1}

## 1.1 Problem Statement

Currently, `HomeApi.php` serves template JSON by reading from disk files:
```php
// CURRENT CODE (repeated ~8 times across HomeApi.php)
// Location: app/Http/Controllers/Api/HomeApi.php
if (is_dir('./uploads/template/'.$zip_name.'/json/')) {
    $file = scandir('./uploads/template/'.$zip_name.'/json/', 1);
    if (isset($file[0]) && $file[0] != '.' && $file[0] != '..') {
        $json_data = file_get_contents(public_path('uploads/template/'.$zip_name.'/json/'.$file[0]));
    }
}
```

**Issues:**
1. `scandir()` + `file_get_contents()` = disk I/O on every request
2. JSON duplicated in multiple places (ZIP file, extracted dir, DB `layers_json` column)
3. Only `customPost()` (L510-517) defaults `render_version` to `1` for legacy frames — all other endpoints don't
4. Only `customPost()` uses `Cache::remember()` — other endpoints don't cache at all

## 1.2 Target Architecture

Replace ALL disk-based JSON reads with DB column reads. The `EditorTemplate.schema_json` and `PosterMaker.layers_json` columns ALREADY contain the complete template JSON in the database.

## 1.3 Files to Modify

| File | Path | What to Change |
|:---|:---|:---|
| `HomeApi.php` | `app/Http/Controllers/Api/HomeApi.php` | Replace disk reads with DB reads |

## 1.4 Exact Code Changes

### 1.4.1 Create a Private Helper Method (Add at ~Line 60)

Add this reusable helper method to `HomeApi` class to eliminate the repeated disk I/O pattern:

```php
/**
 * Resolve template JSON from the fastest available source.
 * Priority: 1) DB layers_json/schema_json  2) Disk file (fallback)
 *
 * @param  string      $zipName  The zip_name identifier (e.g. "Template_abc123")
 * @param  int|null    $frameId  Optional PosterMaker ID for DB lookup
 * @return string|null Raw JSON string, or null if not found
 */
private function resolveTemplateJson(string $zipName, ?int $frameId = null): ?string
{
    // === SOURCE 1: DB Column (fastest — no disk I/O) ===
    if ($frameId) {
        $poster = \App\Models\PosterMaker::find($frameId);
        if ($poster && !empty($poster->layers_json)) {
            $jsonData = is_array($poster->layers_json)
                ? json_encode($poster->layers_json, JSON_UNESCAPED_SLASHES)
                : $poster->layers_json;
            return $this->ensureRenderVersion($jsonData);
        }
    }

    // Try EditorTemplate by UUID
    $editorTemplate = \App\Models\EditorTemplate::where('uuid', $zipName)->first();
    if ($editorTemplate && !empty($editorTemplate->legacy_json)) {
        $jsonData = is_array($editorTemplate->legacy_json)
            ? json_encode($editorTemplate->legacy_json, JSON_UNESCAPED_SLASHES)
            : $editorTemplate->legacy_json;
        return $this->ensureRenderVersion($jsonData);
    }

    // === SOURCE 2: Disk File (fallback for frames without DB JSON) ===
    $jsonDir = public_path('uploads/template/' . $zipName . '/json/');
    if (is_dir($jsonDir)) {
        $files = scandir($jsonDir, 1);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                $jsonData = file_get_contents($jsonDir . $f);
                if ($jsonData) {
                    return $this->ensureRenderVersion($jsonData);
                }
            }
        }
    }

    return null;
}

/**
 * Ensure every JSON payload has a render_version field (default to 1).
 * This prevents the mobile app from receiving JSON without version info.
 */
private function ensureRenderVersion(string $jsonData): string
{
    $parsed = json_decode($jsonData, true);
    if ($parsed && !isset($parsed['render_version'])) {
        $parsed['render_version'] = 1;
        return json_encode($parsed, JSON_UNESCAPED_SLASHES);
    }
    return $jsonData;
}
```

### 1.4.2 Add Asset URL Helper (Add after resolveTemplateJson)

```php
/**
 * Build the full CDN/server base URL for template assets (images, skins).
 * Used by the mobile app to construct full image URLs from relative paths.
 *
 * @param  string $zipName The template zip_name
 * @return string Full base URL ending with /
 */
private function getTemplateBaseUrl(string $zipName): string
{
    $storage = \App\Models\StorageSetting::getStorageSetting('storage');
    if ($storage === 'DigitalOcean') {
        return \Storage::disk('spaces')->url('uploads/template/' . $zipName) . '/';
    }
    // Local storage
    return str_replace(
        'public/uploads',
        'uploads',
        asset('uploads/template/' . $zipName)
    ) . '/';
}
```

### 1.4.3 Replace Disk Reads in Each Function

**In `customPost()` (~Line 490-520) — Replace the disk read block:**

FIND this pattern (approximately):
```php
$json_data = '';
// ... scandir + file_get_contents block ...
if (!empty($json_data)) {
    $tmpParsed = json_decode($json_data, true);
    if ($tmpParsed && !isset($tmpParsed['render_version'])) {
        $tmpParsed['render_version'] = 1;
        $json_data = json_encode($tmpParsed);
    }
}
```

REPLACE WITH:
```php
$json_data = $this->resolveTemplateJson($zip_name, $template->id ?? null) ?? '';
```

**In `customPostPaginated()` (~Line 685-700) — Same replacement:**

FIND the scandir block and REPLACE WITH:
```php
$json_data = $this->resolveTemplateJson($zip_name, $template->id ?? null) ?? '';
```

**In `getCustomFrame()` (~Line 3270-3290) — Same replacement:**
```php
$json_data = $this->resolveTemplateJson($zip_name) ?? '';
```

**In `getBusinessFrame()` (~Line 3660-3680) — Same replacement:**
```php
$json_data = $this->resolveTemplateJson($zip_name) ?? '';
```

**In `getFrames()` (~Line 4240-4260) — Same replacement:**
```php
$json_data = $this->resolveTemplateJson($zip_name, $frame->id ?? null) ?? '';
```

**In `search()` (~Line 1260-1280) — Same replacement:**
```php
$json_data = $this->resolveTemplateJson($zip_name) ?? '';
```

**In `getHomeData()` feature section (~Line 250-270) — Same replacement:**
```php
$json_data = $this->resolveTemplateJson($zip_name) ?? '';
```

### 1.4.4 Add `templateBaseUrl` to ALL Endpoints

Currently only `customPost()` (L608) includes `templateBaseUrl` in its response. Add it to ALL endpoints that serve template JSON:

In every endpoint's response array where `"json" => $json_data` exists, ADD:
```php
'templateBaseUrl' => $this->getTemplateBaseUrl($zip_name),
```

### 1.4.5 Add `render_version` as Top-Level API Field

Currently `render_version` is embedded INSIDE the JSON string. For easier mobile parsing, also add it as a top-level field:

In every endpoint's response array, ADD:
```php
'render_version' => $frame->render_version ?? 1,
```

## 1.5 Verification

After changes, verify:
1. Open the app → Navigate to any template category
2. Templates should load exactly as before (no visual change)
3. Check Laravel logs — no `file_get_contents` errors
4. Verify `render_version` field appears in API JSON response

---
---

# STEP 2: Mobile App Hybrid Renderer & Asset Caching (Flutter) {#step-2}

## 2.1 Problem Statement

Currently, the mobile app downloads ZIP files, extracts them to disk, and reads JSON + images from local filesystem. We need to switch to:
- **JSON**: Directly from API response (already a JSON string in `"json"` field)
- **Images**: Via `CachedNetworkImage` using CDN URLs

## 2.2 Current Architecture (What Exists)

### How Templates Currently Load:

1. **`native_editor_screen.dart`** receives template data (passed via navigation)
2. **`native_editor_controller.dart` → `initConfig()`** (Line 121) initializes the config:
   ```dart
   void initConfig(Map<String, dynamic> initialConfig, String tplBaseUrl,
       String upBaseUrl, String? baseImg, String editorType) {
     templateConfig.assignAll(jsonDecode(jsonEncode(initialConfig))); // deep copy
     templateBaseUrl = tplBaseUrl;
     uploadsBaseUrl = upBaseUrl;
     baseImgUrl = baseImg;
     templateConfig['type'] = editorType;
     templateConfig['render_version'] ??= 1;
     // ...
   }
   ```

3. **`editor_canvas_widget.dart` → `build()` method** (Line 592) reads config and renders:
   ```dart
   final double scale = widget.width / designW;  // Line 600
   final int renderVersion = (widget.config['render_version'] ?? 1) as int;  // Line 607
   ```

4. **Image loading** in `_buildImage()` (Lines 1704-1857) ALREADY supports:
   - Local files: `Image.file(File(url))` — Line 1715
   - Base64 data URIs: `Image.memory(bytes)` — Line 1735
   - Network URLs: `CachedNetworkImage(imageUrl: url)` — **Line 1777** ✅ Already exists!
   - Small assets (<50px): `Image.network(url)` — Line 1756

5. **`cached_network_image`** package is **already in `pubspec.yaml`** at version `^3.4.1` ✅

### KEY INSIGHT:
The Flutter app **already supports CachedNetworkImage** for network URLs! The main change needed is:
- Making sure ALL image `src` values are full HTTP URLs (not relative paths like `../skins/bg.png`)
- The URL resolution already happens in `_resolveAssetUrl()` (Lines 1451-1474) and in `loadNewFrame()` (Lines 840-876)

## 2.3 Files to Modify

| File | Path | What to Change |
|:---|:---|:---|
| `native_editor_controller.dart` | `brandkit_mobile/lib/controllers/native_editor_controller.dart` | Update `initConfig` to handle `templateBaseUrl` from new API response |
| `editor_canvas_widget.dart` | `brandkit_mobile/lib/widgets/editor_canvas_widget.dart` | Update `_resolveAssetUrl` to prefer full HTTP URLs from API |

## 2.4 Exact Code Changes

### 2.4.1 Update `_resolveAssetUrl()` in `editor_canvas_widget.dart` (Lines 1451-1474)

The current URL resolution handles relative paths like `../skins/img.png`. With the new API serving full `templateBaseUrl`, we need to ensure relative paths get properly resolved:

**CURRENT CODE (~Line 1451):**
```dart
String _resolveAssetUrl(String rawSrc) {
    // ... existing logic ...
}
```

**NO CHANGES NEEDED HERE** — the existing `_resolveAssetUrl()` already handles:
- Full HTTP URLs (returns as-is)
- `../skins/` relative paths (resolves against `widget.templateBaseUrl`)
- Bare filenames (resolves against `widget.templateBaseUrl`)

The critical thing is that `templateBaseUrl` must be correctly passed from the API response to this widget.

### 2.4.2 Ensure `templateBaseUrl` Flows from API → Controller → Widget

The flow is:
```
API Response → native_editor_screen.dart → initConfig(templateBaseUrl) → EditorCanvas(templateBaseUrl)
```

Check how templates are displayed. Search for where `initConfig` is called:

**In the screen that launches the editor**, ensure `templateBaseUrl` from the API response is passed:

```dart
// When navigating to native editor screen, extract templateBaseUrl from API data:
final templateBaseUrl = templateData['templateBaseUrl'] ?? '';
// Pass it to initConfig:
controller.initConfig(
    jsonDecode(templateData['json']),  // The template JSON
    templateBaseUrl,                    // Full base URL for assets
    uploadsBaseUrl,                     // Upload base URL
    baseImgUrl,                         // Background image URL
    templateData['type'] ?? 'business_custom_frame',
);
```

### 2.4.3 Add Pre-Export Safety Check (`_checkAssetsReady`)

**File:** `brandkit_mobile/lib/screens/native_editor_screen.dart`
**Location:** Before the export call at Line 68

**CURRENT export code (Line 68):**
```dart
final ui.Image image = await boundary.toImage(pixelRatio: 3.0);
```

**ADD BEFORE this line — a safety check that all CachedNetworkImages are loaded:**

```dart
/// Checks that all network images in the template are cached and ready for export.
/// Returns true if all assets are loaded, false if any are still loading.
Future<bool> _checkAssetsReady(Map<String, dynamic> config) async {
    final layers = config['layers'] as List<dynamic>? ?? [];
    for (var layer in layers) {
        if (layer['type'] == 'image' && layer['src'] != null) {
            String src = layer['src'].toString();
            if (src.startsWith('http')) {
                // Check if the image is in the cache
                try {
                    final fileInfo = await DefaultCacheManager().getFileFromCache(src);
                    if (fileInfo == null) {
                        // Image not cached yet — try downloading it
                        debugPrint('[EXPORT_CHECK] Image not cached, downloading: $src');
                        await DefaultCacheManager().downloadFile(src);
                    }
                } catch (e) {
                    debugPrint('[EXPORT_CHECK] Failed to verify asset: $src — $e');
                    return false;
                }
            }
        }
    }
    return true;
}
```

**Then wrap the export with the safety check:**
```dart
// Before export
final assetsReady = await _checkAssetsReady(controller.templateConfig);
if (!assetsReady) {
    // Show a snackbar warning
    Get.snackbar('Loading...', 'Some images are still downloading. Please wait a moment.',
        snackPosition: SnackPosition.BOTTOM);
    return;
}

// Proceed with export
final ui.Image image = await boundary.toImage(pixelRatio: 3.0);
```

**Required import (add at top of file):**
```dart
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
```

**Required pubspec.yaml check:** `flutter_cache_manager` is a transitive dependency of `cached_network_image` (already in pubspec), so no additional package needed. But verify it's importable — if not, add:
```yaml
flutter_cache_manager: ^3.4.1
```

## 2.5 Verification

1. Open any template in the native editor
2. All images should load (from cache on second open)
3. Export the poster as Ultra HD image → should NOT have blank/missing images
4. Check logs for `[EXPORT_CHECK]` messages

---
---

# STEP 3: Redis Cache + ETag 304 Smart Sync {#step-3}

## 3.1 Problem Statement

Even with DB reads, MySQL queries on every API call will be slow under festival load (lakhon concurrent users). We need:
1. **Redis**: Cache template JSON in RAM (5ms reads vs 50ms MySQL queries)
2. **ETag/304**: Don't re-download unchanged JSON (save bandwidth)

## 3.2 Prerequisites

### Install Redis on the server (if not already):
```bash
sudo apt update
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping  # Should return "PONG"
```

### Configure Laravel to use Redis cache:
**File:** `.env`
```
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**File:** `config/database.php` — Ensure Redis configuration exists (Laravel default has it)

### Install phpredis or predis:
```bash
# Option A: phpredis (recommended, faster)
sudo apt install php-redis
sudo systemctl restart apache2

# Option B: predis (pure PHP, no extension needed)
composer require predis/predis
```

## 3.3 Files to Modify

| File | Path | What to Change |
|:---|:---|:---|
| `HomeApi.php` | `app/Http/Controllers/Api/HomeApi.php` | Add Redis caching to `resolveTemplateJson()` |
| `TemplateBuilderController.php` | `app/Http/Controllers/Admin/TemplateBuilderController.php` | Invalidate Redis cache on Publish |
| `PosterMakerController.php` | `app/Http/Controllers/Admin/PosterMakerController.php` | Invalidate Redis cache on Bulk Migrate |
| `native_editor_controller.dart` | `brandkit_mobile/lib/controllers/native_editor_controller.dart` | Add ETag/304 conditional requests |

## 3.4 Exact Code Changes

### 3.4.1 Add Redis Caching to `resolveTemplateJson()` in `HomeApi.php`

Replace the `resolveTemplateJson` helper from Step 1 with this Redis-enhanced version:

```php
private function resolveTemplateJson(string $zipName, ?int $frameId = null): ?string
{
    $cacheKey = "template_json:{$zipName}";
    $cacheTTL = 3600; // 1 hour

    // === SOURCE 1: Redis Cache (fastest — 5ms) ===
    $cached = \Cache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    // === SOURCE 2: DB Column (fast — 20-50ms) ===
    $jsonData = null;

    if ($frameId) {
        $poster = \App\Models\PosterMaker::find($frameId);
        if ($poster && !empty($poster->layers_json)) {
            $jsonData = is_array($poster->layers_json)
                ? json_encode($poster->layers_json, JSON_UNESCAPED_SLASHES)
                : $poster->layers_json;
        }
    }

    if ($jsonData === null) {
        $editorTemplate = \App\Models\EditorTemplate::where('uuid', $zipName)->first();
        if ($editorTemplate && !empty($editorTemplate->legacy_json)) {
            $jsonData = is_array($editorTemplate->legacy_json)
                ? json_encode($editorTemplate->legacy_json, JSON_UNESCAPED_SLASHES)
                : $editorTemplate->legacy_json;
        }
    }

    // === SOURCE 3: Disk File (slowest fallback — 100-200ms) ===
    if ($jsonData === null) {
        $jsonDir = public_path('uploads/template/' . $zipName . '/json/');
        if (is_dir($jsonDir)) {
            $files = scandir($jsonDir, 1);
            foreach ($files as $f) {
                if ($f !== '.' && $f !== '..') {
                    $jsonData = file_get_contents($jsonDir . $f);
                    break;
                }
            }
        }
    }

    if ($jsonData === null) return null;

    // Ensure render_version exists
    $jsonData = $this->ensureRenderVersion($jsonData);

    // Store in Redis for next request
    \Cache::put($cacheKey, $jsonData, $cacheTTL);

    return $jsonData;
}
```

### 3.4.2 Add `last_updated` to API Response

In EACH endpoint that returns template JSON, add the `updated_at` timestamp for ETag sync:

```php
// In the response array, add:
'updated_at' => $frame->updated_at?->toIso8601String() ?? null,
```

### 3.4.3 Add ETag/304 Conditional Request Support in `HomeApi.php`

Add this method to handle conditional requests:

```php
/**
 * Check if client's cached version is still fresh.
 * Returns HTTP 304 (Not Modified) if no update needed.
 *
 * Usage: Call at the start of any API endpoint that returns template data.
 */
private function checkConditionalRequest(Request $request, string $zipName): ?\Illuminate\Http\Response
{
    $clientTimestamp = $request->query('last_updated');
    if (!$clientTimestamp) return null;

    // Check if template has been updated since client's version
    $poster = \App\Models\PosterMaker::where('zip_name', $zipName)->first();
    if (!$poster) return null;

    $serverTimestamp = $poster->updated_at?->toIso8601String();

    if ($serverTimestamp && $clientTimestamp === $serverTimestamp) {
        return response('', 304); // Not Modified — client has latest version
    }

    return null; // Modified — send full response
}
```

### 3.4.4 Invalidate Redis on Publish (`TemplateBuilderController.php`)

**EXISTING CODE at Line 1122-1133 already clears cache!** Verify it uses the same key:

```php
// Line 1122-1133 in saveFrame()
Cache::forget("template_json:{$templateName}");
// Also forget with .zip suffix and old name
```

This already works ✅. Just ensure the cache key format matches: `template_json:{zipName}`.

### 3.4.5 Invalidate Redis on Bulk Migration (`PosterMakerController.php`)

**In `bulkMigrateVersion()` (Line 889), after saving each frame, ADD:**

```php
// After line 999 (after $frame->save()):
\Cache::forget("template_json:{$frame->zip_name}");

// After line 1043 (after $editorTemplate->save()):
\Cache::forget("template_json:{$editorTemplate->uuid}");
```

### 3.4.6 Mobile App: ETag/304 Conditional Sync (Flutter)

**File:** `brandkit_mobile/lib/controllers/native_editor_controller.dart`

Add a local cache manager for template JSON:

```dart
import 'package:shared_preferences/shared_preferences.dart';

/// Cache template JSON locally with last_updated timestamp for 304 sync.
class TemplateJsonCache {
    static const _prefix = 'tpl_cache_';
    static const _tsPrefix = 'tpl_ts_';

    /// Get cached JSON for a template, or null if not cached.
    static Future<String?> getCached(String zipName) async {
        final prefs = await SharedPreferences.getInstance();
        return prefs.getString('$_prefix$zipName');
    }

    /// Get the last_updated timestamp for a cached template.
    static Future<String?> getTimestamp(String zipName) async {
        final prefs = await SharedPreferences.getInstance();
        return prefs.getString('$_tsPrefix$zipName');
    }

    /// Save template JSON and its timestamp to local cache.
    static Future<void> save(String zipName, String json, String? updatedAt) async {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('$_prefix$zipName', json);
        if (updatedAt != null) {
            await prefs.setString('$_tsPrefix$zipName', updatedAt);
        }
    }
}
```

**Usage when fetching a single template:**

```dart
Future<Map<String, dynamic>?> fetchTemplateJson(String zipName) async {
    // Check local cache first
    final cachedTs = await TemplateJsonCache.getTimestamp(zipName);

    // Build URL with conditional param
    String url = '/api/template/$zipName';
    if (cachedTs != null) {
        url += '?last_updated=$cachedTs';
    }

    final response = await ApiService.get(url);

    if (response.statusCode == 304) {
        // Server says our cache is fresh — use local copy
        final cached = await TemplateJsonCache.getCached(zipName);
        if (cached != null) {
            return jsonDecode(cached);
        }
    }

    if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // Save to local cache for next time
        await TemplateJsonCache.save(
            zipName,
            response.body,
            data['updated_at'],
        );
        return data;
    }

    return null;
}
```

## 3.5 Verification

1. First API call → Redis MISS → DB query → Redis SET → Response (50ms)
2. Second API call → Redis HIT → Response (5ms) ← 10x faster!
3. After Admin publishes → Redis invalidated → Next call fetches fresh data
4. Mobile app second load → sends `last_updated` → gets 304 (0 bytes payload)

---
---

# STEP 4: Bundled Batch API {#step-4}

## 4.1 Problem Statement

When the detail list screen loads 20 templates, the app makes 20 separate API calls. We need a single batch endpoint.

## 4.2 Files to Create/Modify

| File | Path | Action |
|:---|:---|:---|
| `HomeApi.php` | `app/Http/Controllers/Api/HomeApi.php` | Add new batch endpoint |
| `api.php` | `routes/api.php` | Add new route |

## 4.3 Exact Code

### 4.3.1 Add Batch Route (`routes/api.php`)

```php
Route::get('/templates/batch', [HomeApi::class, 'batchTemplates'])->middleware('throttle');
```

### 4.3.2 Add `batchTemplates()` Method to `HomeApi.php`

```php
/**
 * Batch fetch template JSON for multiple templates in a single request.
 * Supports up to 20 templates per request.
 *
 * GET /api/templates/batch?zip_names=Name1,Name2,Name3
 * GET /api/templates/batch?ids=1,2,3
 */
public function batchTemplates(Request $request)
{
    $maxBatch = 20;

    // Accept either zip_names or ids
    $zipNames = $request->query('zip_names')
        ? explode(',', $request->query('zip_names'))
        : [];
    $ids = $request->query('ids')
        ? array_map('intval', explode(',', $request->query('ids')))
        : [];

    // Limit batch size
    $zipNames = array_slice($zipNames, 0, $maxBatch);
    $ids = array_slice($ids, 0, $maxBatch);

    $results = [];

    // Fetch by IDs
    if (!empty($ids)) {
        $frames = \App\Models\PosterMaker::whereIn('id', $ids)->get();
        foreach ($frames as $frame) {
            $jsonData = $this->resolveTemplateJson($frame->zip_name, $frame->id);
            $results[] = [
                'id' => $frame->id,
                'zip_name' => $frame->zip_name,
                'render_version' => $frame->render_version ?? 1,
                'json' => $jsonData,
                'templateBaseUrl' => $this->getTemplateBaseUrl($frame->zip_name),
                'updated_at' => $frame->updated_at?->toIso8601String(),
            ];
        }
    }

    // Fetch by zip_names
    if (!empty($zipNames)) {
        $frames = \App\Models\PosterMaker::whereIn('zip_name', $zipNames)->get();
        foreach ($frames as $frame) {
            $jsonData = $this->resolveTemplateJson($frame->zip_name, $frame->id);
            $results[] = [
                'id' => $frame->id,
                'zip_name' => $frame->zip_name,
                'render_version' => $frame->render_version ?? 1,
                'json' => $jsonData,
                'templateBaseUrl' => $this->getTemplateBaseUrl($frame->zip_name),
                'updated_at' => $frame->updated_at?->toIso8601String(),
            ];
        }
    }

    return response()->json([
        'success' => true,
        'count' => count($results),
        'templates' => $results,
    ]);
}
```

### 4.3.3 Flutter: Use Batch API in Detail List Screen

```dart
/// Fetch multiple templates in a single HTTP request.
Future<List<Map<String, dynamic>>> batchFetchTemplates(List<int> ids) async {
    final idsStr = ids.take(20).join(',');
    final response = await ApiService.get('/templates/batch?ids=$idsStr');
    if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
            return List<Map<String, dynamic>>.from(data['templates'] ?? []);
        }
    }
    return [];
}
```

---
---

# STEP 5: Golden Snapshot Baseline System {#step-5}

## 5.1 Problem Statement

When version migration happens, we need a "source of truth" to compare against. Golden Snapshots capture the EXACT computed values (x, y, w, h, fontSize) of each layer when a frame was FIRST published and confirmed as rendering correctly.

## 5.2 Database Migration

### 5.2.1 Create Migration

```bash
php artisan make:migration create_golden_renders_table
```

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_golden_renders_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golden_renders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frame_id')->index();
            $table->string('zip_name')->index();
            $table->integer('render_version')->default(1);
            $table->longText('web_computed')->nullable();    // JSON: per-layer web computed values
            $table->longText('native_computed')->nullable();  // JSON: per-layer native computed values
            $table->string('web_thumbnail_path')->nullable(); // Path to web preview screenshot
            $table->string('native_snapshot_path')->nullable(); // Path to native preview screenshot
            $table->string('source')->default('publish');     // 'publish', 'migration', 'manual'
            $table->timestamps();

            // Unique constraint: one golden per frame per version
            $table->unique(['frame_id', 'render_version'], 'golden_frame_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golden_renders');
    }
};
```

### 5.2.2 Create Model

**File:** `app/Models/GoldenRender.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldenRender extends Model
{
    protected $fillable = [
        'frame_id',
        'zip_name',
        'render_version',
        'web_computed',
        'native_computed',
        'web_thumbnail_path',
        'native_snapshot_path',
        'source',
    ];

    protected $casts = [
        'web_computed' => 'array',
        'native_computed' => 'array',
        'render_version' => 'integer',
    ];

    /**
     * Get the PosterMaker frame this golden render belongs to.
     */
    public function frame()
    {
        return $this->belongsTo(PosterMaker::class, 'frame_id');
    }

    /**
     * Get or create a golden render for a specific frame + version.
     */
    public static function capture(int $frameId, string $zipName, int $version, array $data): self
    {
        return self::updateOrCreate(
            ['frame_id' => $frameId, 'render_version' => $version],
            array_merge(['zip_name' => $zipName], $data)
        );
    }
}
```

### 5.2.3 Run Migration

```bash
php artisan migrate
```

## 5.3 Capture Web Golden Baseline on Publish

**File:** `app/Http/Controllers/Admin/TemplateBuilderController.php`
**Location:** Inside `saveFrame()`, AFTER the EditorTemplate is saved (after ~Line 886)

Add this block:

```php
// === GOLDEN SNAPSHOT: Capture Web Computed Values ===
$webComputed = [];
$schemaObjects = $schemaJson['objects'] ?? $schemaJson['layers'] ?? [];
foreach ($schemaObjects as $obj) {
    $layerName = $obj['name'] ?? $obj['id'] ?? 'unknown';
    $webComputed[$layerName] = [
        'canvasX' => $obj['x'] ?? $obj['left'] ?? 0,
        'canvasY' => $obj['y'] ?? $obj['top'] ?? 0,
        'canvasW' => $obj['w'] ?? $obj['width'] ?? 0,
        'canvasH' => $obj['h'] ?? $obj['height'] ?? 0,
        'computedFontSize' => $obj['fontSize'] ?? $obj['font_size'] ?? $obj['size'] ?? null,
        'fontFamily' => $obj['fontFamily'] ?? $obj['font_name'] ?? $obj['font'] ?? null,
        'fill' => $obj['fill'] ?? $obj['color'] ?? $obj['font_color'] ?? null,
        'type' => $obj['type'] ?? 'unknown',
        'scaleX' => $obj['scaleX'] ?? 1,
        'scaleY' => $obj['scaleY'] ?? 1,
    ];
}

// Save the thumbnail as web_thumbnail
$webThumbPath = null;
if (isset($thumbnailPath)) {
    $webThumbPath = $thumbnailPath;
}

// Determine frame_id (the PosterMaker record)
$goldenFrameId = $frame->id ?? null;
if ($goldenFrameId) {
    \App\Models\GoldenRender::capture(
        $goldenFrameId,
        $templateName,
        (int) ($schemaJson['render_version'] ?? 1),
        [
            'web_computed' => $webComputed,
            'web_thumbnail_path' => $webThumbPath,
            'source' => 'publish',
        ]
    );
}
```

## 5.4 Capture Native Golden Baseline (via API)

The native app can send its computed values after first successful render. Add an API endpoint:

### 5.4.1 Add Route (`routes/api.php`)

```php
Route::post('/golden-render/capture-native', [HomeApi::class, 'captureNativeGolden'])->middleware('throttle');
```

### 5.4.2 Add Method to `HomeApi.php`

```php
/**
 * Capture native computed values from the Flutter app after first render.
 * Called once per frame per version from the native editor.
 *
 * POST /api/golden-render/capture-native
 * Body: { frame_id, zip_name, render_version, native_computed: { layerName: { finalX, finalY, ... } } }
 */
public function captureNativeGolden(Request $request)
{
    $request->validate([
        'frame_id' => 'required|integer',
        'zip_name' => 'required|string',
        'render_version' => 'required|integer',
        'native_computed' => 'required|array',
    ]);

    // Only capture if no native_computed exists yet for this frame+version
    $existing = \App\Models\GoldenRender::where('frame_id', $request->frame_id)
        ->where('render_version', $request->render_version)
        ->first();

    if ($existing && !empty($existing->native_computed)) {
        return response()->json(['success' => true, 'message' => 'Native golden already captured']);
    }

    \App\Models\GoldenRender::capture(
        $request->frame_id,
        $request->zip_name,
        $request->render_version,
        [
            'native_computed' => $request->native_computed,
            'source' => $existing ? $existing->source : 'native_auto',
        ]
    );

    return response()->json(['success' => true, 'message' => 'Native golden captured']);
}
```

### 5.4.3 Flutter: Send Native Computed Values After First Render

**File:** `brandkit_mobile/lib/widgets/editor_canvas_widget.dart`
**Location:** At the END of the `build()` method, after all layers are built

```dart
// === GOLDEN SNAPSHOT: Capture native computed values (once per frame) ===
// Only run this in non-edit mode (preview/initial load), not on every rebuild
if (!_goldenCaptured && widget.config['_golden_sent'] != true) {
    _captureNativeGolden(stackChildren, scale, renderVersion);
}
```

Add the capture method:

```dart
bool _goldenCaptured = false;

void _captureNativeGolden(List<Widget> children, double scale, int renderVersion) {
    // Debounce — only send once
    _goldenCaptured = true;
    widget.config['_golden_sent'] = true;

    // Build native_computed map from current layer data
    final layers = widget.config['layers'] as List<dynamic>? ?? [];
    final Map<String, Map<String, dynamic>> nativeComputed = {};

    for (var layer in layers) {
        final name = (layer['name'] ?? layer['id'] ?? '').toString();
        if (name.isEmpty) continue;

        final double x = safeDouble(layer['x'] ?? 0);
        final double y = safeDouble(layer['y'] ?? 0);
        final double w = safeDouble(layer['w'] ?? layer['width'] ?? 0);
        final double h = safeDouble(layer['h'] ?? layer['height'] ?? 0);

        nativeComputed[name] = {
            'finalX': (x * scale).roundToDouble(),
            'finalY': (y * scale).roundToDouble(),
            'finalW': (w * scale).roundToDouble(),
            'finalH': (h * scale).roundToDouble(),
            'type': layer['type'] ?? 'unknown',
        };

        // For text layers, also capture computed font size
        if (layer['type'] == 'text') {
            final double rawSize = safeDouble(layer['fontSize'] ?? layer['font_size'] ?? layer['size'] ?? 16);
            final double ppiScale = safeDouble(widget.config['info']?['ppi'] ?? 72) / 72.0;
            final double layerScaleY = safeDouble(layer['scaleY'] ?? 1);
            nativeComputed[name]!['finalFontSize'] = (rawSize * ppiScale * layerScaleY * scale).roundToDouble();
        }
    }

    // Get frame info
    final frameId = widget.config['info']?['id'] ?? widget.config['id'];
    final zipName = widget.config['info']?['zip_name'] ?? widget.config['zip_name'] ?? '';

    if (frameId != null && zipName.toString().isNotEmpty) {
        // Send to server (fire and forget — don't block render)
        ApiService.post('/golden-render/capture-native', {
            'frame_id': frameId,
            'zip_name': zipName,
            'render_version': renderVersion,
            'native_computed': nativeComputed,
        }).catchError((e) {
            debugPrint('[GOLDEN] Error sending native golden: $e');
        });
    }
}
```

---
---

# STEP 6: Web Editor One-Click Diff Review Modal {#step-6}

## 6.1 Problem Statement

When an old template (V1) is opened in the Web Editor and saved, it gets silently upgraded to V4. We need a Diff Review Modal that shows exactly what changed before committing.

## 6.2 Files to Modify

| File | Path | What to Change |
|:---|:---|:---|
| `template_builder.js` | `assets/js/template_builder.js` | Add diff calculation before saveFrame |
| `template_builder.blade.php` | `resources/views/template_builder/index.blade.php` | Add Diff Review Modal HTML/CSS |

## 6.3 Implementation

### 6.3.1 Add Diff Engine to `template_builder.js`

**IMPORTANT:** This code must NOT modify any locked functions (`_doRender`, `exportArteraSchema`, `exportLegacyJson`). It is a NEW standalone function.

Add AFTER the `exportLegacyJson()` function (~Line 5060):

```javascript
/**
 * Compare old JSON (loaded version) vs new JSON (about to save).
 * Returns an array of diffs per layer.
 *
 * @param {Object} oldJson - The JSON that was loaded when template opened
 * @param {Object} newJson - The JSON that exportArteraSchema() just generated
 * @returns {Array<{layerName, property, oldValue, newValue, type}>}
 */
function computeVersionDiff(oldJson, newJson) {
    const diffs = [];
    const oldVersion = oldJson.render_version || 1;
    const newVersion = newJson.render_version || CURRENT_RENDER_VERSION;

    // Add version change itself
    if (oldVersion !== newVersion) {
        diffs.push({
            layerName: '(Template)',
            property: 'render_version',
            oldValue: 'V' + oldVersion,
            newValue: 'V' + newVersion,
            type: 'version_upgrade',
        });
    }

    // Build layer maps by name
    const oldLayers = {};
    const newLayers = {};
    (oldJson.layers || oldJson.objects || []).forEach(l => {
        oldLayers[l.name || l.id || 'unknown'] = l;
    });
    (newJson.layers || newJson.objects || []).forEach(l => {
        newLayers[l.name || l.id || 'unknown'] = l;
    });

    // Compare properties for each layer
    const propsToCompare = ['x', 'y', 'w', 'h', 'width', 'height', 'fontSize', 'font_size',
        'size', 'type', 'fill', 'color', 'font_color', 'fontFamily', 'font_name',
        'scaleX', 'scaleY', 'rx', 'ry', 'stroke', 'strokeWidth', 'shapeType'];

    const allLayerNames = new Set([...Object.keys(oldLayers), ...Object.keys(newLayers)]);

    allLayerNames.forEach(name => {
        const oldL = oldLayers[name];
        const newL = newLayers[name];

        if (!oldL && newL) {
            diffs.push({
                layerName: name,
                property: '(entire layer)',
                oldValue: '—',
                newValue: 'NEW (added by V' + newVersion + ')',
                type: 'layer_added',
            });
            return;
        }

        if (oldL && !newL) {
            diffs.push({
                layerName: name,
                property: '(entire layer)',
                oldValue: 'Existed in V' + oldVersion,
                newValue: 'REMOVED',
                type: 'layer_removed',
            });
            return;
        }

        // Both exist — compare properties
        propsToCompare.forEach(prop => {
            const ov = oldL[prop];
            const nv = newL[prop];
            if (ov !== undefined || nv !== undefined) {
                // Normalize for comparison
                const ovStr = JSON.stringify(ov ?? null);
                const nvStr = JSON.stringify(nv ?? null);
                if (ovStr !== nvStr) {
                    diffs.push({
                        layerName: name,
                        property: prop,
                        oldValue: ov ?? '—',
                        newValue: nv ?? '—',
                        type: typeof ov === 'number' && typeof nv === 'number' ? 'numeric_shift' : 'value_change',
                    });
                }
            }
        });

        // Check type upgrade (e.g., raster PNG → vector shape)
        if (oldL.type === 'image' && (newL.type === 'shape' || newL.type === 'rect')) {
            diffs.push({
                layerName: name,
                property: 'type',
                oldValue: 'Raster PNG (' + (oldL.src ? (oldL.src.length > 30 ? oldL.src.substring(0,30) + '...' : oldL.src) : 'no src') + ')',
                newValue: 'Vector Shape (fill: ' + (newL.fill || newL.color || '?') + ')',
                type: 'type_upgrade',
            });
        }
    });

    return diffs;
}
```

### 6.3.2 Store Original JSON on Template Load

When a template is loaded in the editor, save a copy of the original JSON for later diff comparison.

Add after the template loads (in the `loadZip` callback or after `_doRender` completes):

```javascript
// Store original JSON for diff comparison (read-only copy)
window._originalLoadedJson = JSON.parse(JSON.stringify(
    canvas.toJSON(['name', 'id', 'customAttrs']) // or the raw loaded JSON
));
window._originalRenderVersion = window._originalLoadedJson.render_version || 1;
```

### 6.3.3 Hook Into Publish Button

Modify the publish button handler to show the diff modal BEFORE sending to server:

```javascript
// In the publish button click handler (but NOT inside exportArteraSchema or saveFrame — those are LOCKED)
function handlePublishClick() {
    // Generate new JSON
    const newSchema = exportArteraSchema();
    const newLegacy = exportLegacyJson();

    // Check if version upgrade happened
    if (window._originalRenderVersion < CURRENT_RENDER_VERSION) {
        // Compute diff
        const diffs = computeVersionDiff(window._originalLoadedJson, newLegacy);

        if (diffs.length > 0) {
            // Show diff modal instead of direct save
            showDiffReviewModal(diffs, newSchema, newLegacy);
            return;
        }
    }

    // No version change or no diffs — proceed with normal save
    doSaveFrame(newSchema, newLegacy);
}
```

### 6.3.4 Diff Review Modal HTML

Add to the template builder Blade view:

```html
<!-- Version Diff Review Modal -->
<div id="diffReviewModal" class="modal fade" tabindex="-1" style="display:none;">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background:#1a1a2e; color:#e0e0e0; border:1px solid #333;">
            <div class="modal-header" style="border-bottom:1px solid #333;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;">
                    <i class="fas fa-code-compare"></i> Version Upgrade Review
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeDiffModal()"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="background:#162447; border:1px solid #1f4068; color:#c7d5e0;">
                    <strong>Version Upgrade:</strong> <span id="diffVersionLabel">V1 → V4</span>
                    <br><small>Review the changes below before publishing.</small>
                </div>
                <table class="table table-sm" style="color:#e0e0e0;">
                    <thead>
                        <tr style="border-bottom:2px solid #444;">
                            <th>Layer</th>
                            <th>Property</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody id="diffTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer" style="border-top:1px solid #333;">
                <button class="btn btn-outline-secondary" onclick="closeDiffModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-success" onclick="approveAndPublish()">
                    <i class="fas fa-check-circle"></i> Approve & Publish
                </button>
            </div>
        </div>
    </div>
</div>
```

### 6.3.5 Diff Modal JavaScript Functions

```javascript
function showDiffReviewModal(diffs, newSchema, newLegacy) {
    window._pendingSchema = newSchema;
    window._pendingLegacy = newLegacy;

    const tbody = document.getElementById('diffTableBody');
    tbody.innerHTML = '';

    document.getElementById('diffVersionLabel').textContent =
        'V' + (window._originalRenderVersion) + ' → V' + CURRENT_RENDER_VERSION;

    diffs.forEach(d => {
        const tr = document.createElement('tr');
        let typeClass = '';
        let typeIcon = '';
        if (d.type === 'version_upgrade') { typeClass = 'color:#4fc3f7'; typeIcon = '🔄'; }
        else if (d.type === 'numeric_shift') { typeClass = 'color:#ffb74d'; typeIcon = '📐'; }
        else if (d.type === 'type_upgrade') { typeClass = 'color:#81c784'; typeIcon = '⬆️'; }
        else if (d.type === 'layer_added') { typeClass = 'color:#66bb6a'; typeIcon = '➕'; }
        else if (d.type === 'layer_removed') { typeClass = 'color:#ef5350'; typeIcon = '➖'; }
        else { typeClass = 'color:#90a4ae'; typeIcon = '✏️'; }

        tr.innerHTML = `
            <td style="font-weight:600;">${d.layerName}</td>
            <td><code style="background:#2a2a4a;padding:2px 6px;border-radius:3px;">${d.property}</code></td>
            <td style="color:#ef5350;">${formatDiffValue(d.oldValue)}</td>
            <td style="color:#66bb6a;">${formatDiffValue(d.newValue)}</td>
            <td style="${typeClass}">${typeIcon} ${d.type.replace('_', ' ')}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('diffReviewModal').style.display = 'flex';
}

function formatDiffValue(val) {
    if (val === null || val === undefined) return '—';
    if (typeof val === 'number') return Math.round(val * 100) / 100;
    if (typeof val === 'object') return JSON.stringify(val).substring(0, 50);
    return String(val).substring(0, 50);
}

function closeDiffModal() {
    document.getElementById('diffReviewModal').style.display = 'none';
    window._pendingSchema = null;
    window._pendingLegacy = null;
}

function approveAndPublish() {
    closeDiffModal();
    if (window._pendingSchema && window._pendingLegacy) {
        doSaveFrame(window._pendingSchema, window._pendingLegacy);
    }
}
```

---
---

# STEP 7: Version Dashboard Dual Engine Validation {#step-7}

## 7.1 Problem Statement

When Admin uses Version Control Dashboard to bulk migrate frames (e.g., V1→V4), the current system (`PosterMakerController@bulkMigrateVersion`, Lines 889-1060) blindly updates `render_version` in DB and JSON without checking if the frame will render correctly at the new version.

## 7.2 Architecture: Server-Side Math Simulators

Since we can't run Fabric.js (browser) or Flutter (mobile) on the server, we create **PHP Math Simulators** that replicate the EXACT mathematical formulas used by each engine. These don't render pixels — they only compute the final numeric values (x, y, w, h, fontSize) that would result from applying a given version's logic.

## 7.3 Files to Create

| File | Path | Action |
|:---|:---|:---|
| `WebRenderSimulator.php` | `app/Services/WebRenderSimulator.php` | NEW — Simulates web export math |
| `NativeRenderSimulator.php` | `app/Services/NativeRenderSimulator.php` | NEW — Simulates native render math |
| `DualEngineValidator.php` | `app/Services/DualEngineValidator.php` | NEW — Orchestrates comparison |

## 7.4 Exact Code

### 7.4.1 `WebRenderSimulator.php`

```php
<?php

namespace App\Services;

/**
 * Simulates the Web Editor's export calculations (template_builder.js → exportArteraSchema).
 * For each layer, computes the FINAL values that would be exported at a given render_version.
 *
 * THIS IS A PURE MATH ENGINE — no rendering, no canvas, no browser.
 */
class WebRenderSimulator
{
    /**
     * Compute the web-side export values for all layers in a template JSON.
     *
     * @param  array  $templateJson   The raw template JSON (schema_json or legacy_json)
     * @param  int    $targetVersion  The render_version to simulate
     * @return array  Per-layer computed values: ['layerName' => ['canvasX'=>..., 'canvasW'=>...]]
     */
    public function compute(array $templateJson, int $targetVersion): array
    {
        $results = [];
        $layers = $templateJson['layers'] ?? $templateJson['objects'] ?? [];

        foreach ($layers as $layer) {
            $name = $layer['name'] ?? $layer['id'] ?? 'unknown';
            $type = $layer['type'] ?? 'unknown';

            // Raw values from JSON
            $x = floatval($layer['x'] ?? $layer['left'] ?? 0);
            $y = floatval($layer['y'] ?? $layer['top'] ?? 0);
            $w = floatval($layer['w'] ?? $layer['width'] ?? 0);
            $h = floatval($layer['h'] ?? $layer['height'] ?? 0);
            $scaleX = floatval($layer['scaleX'] ?? 1);
            $scaleY = floatval($layer['scaleY'] ?? 1);
            $fontSize = floatval($layer['fontSize'] ?? $layer['font_size'] ?? $layer['size'] ?? 0);

            // === BAKING FORMULAS (matches exportArteraSchema logic) ===
            if ($targetVersion >= 4) {
                // V4+: Dimensions are baked (width * scaleX), coordinates are absolute top-left
                $bakedW = round($w * $scaleX);
                $bakedH = round($h * $scaleY);
                $bakedX = $x; // Already top-left from setCoords() in web editor
                $bakedY = $y;
                $bakedFontSize = $fontSize > 0 ? round($fontSize * abs($scaleY)) : null;
                $finalScaleX = 1;
                $finalScaleY = 1;
            } else {
                // V1-V3: Raw values preserved (no baking)
                $bakedW = $w;
                $bakedH = $h;
                $bakedX = $x;
                $bakedY = $y;
                $bakedFontSize = $fontSize > 0 ? $fontSize : null;
                $finalScaleX = $scaleX;
                $finalScaleY = $scaleY;
            }

            // V4+ text Y offset adjustment
            $yOffset = 0;
            if ($type === 'text' && $targetVersion >= 4 && $bakedFontSize > 0) {
                $yOffset = $bakedFontSize * 0.12; // Default Y offset factor
            }

            $results[$name] = [
                'canvasX' => round($bakedX, 2),
                'canvasY' => round($bakedY + $yOffset, 2),
                'canvasW' => round($bakedW, 2),
                'canvasH' => round($bakedH, 2),
                'computedFontSize' => $bakedFontSize,
                'scaleX' => $finalScaleX,
                'scaleY' => $finalScaleY,
                'type' => $type,
            ];
        }

        return $results;
    }
}
```

### 7.4.2 `NativeRenderSimulator.php`

```php
<?php

namespace App\Services;

/**
 * Simulates the Native Editor's rendering calculations (editor_canvas_widget.dart → build()).
 * For each layer, computes the FINAL pixel values that Flutter would calculate.
 *
 * THIS IS A PURE MATH ENGINE — no Flutter, no widgets, no rendering.
 */
class NativeRenderSimulator
{
    /**
     * Compute native-side render values for all layers.
     *
     * @param  array  $templateJson   The template JSON
     * @param  int    $targetVersion  The render_version to simulate
     * @param  float  $deviceWidth    The device screen width (default 360)
     * @return array  Per-layer computed values
     */
    public function compute(array $templateJson, int $targetVersion, float $deviceWidth = 360): array
    {
        $results = [];

        // Design dimensions
        $info = $templateJson['info'] ?? [];
        if (is_string($info)) $info = json_decode($info, true) ?? [];
        $designW = floatval($info['width'] ?? $templateJson['width'] ?? 1080);
        $designH = floatval($info['height'] ?? $templateJson['height'] ?? 1080);
        $docPPI = floatval($info['ppi'] ?? 72);
        $ppiScale = $docPPI / 72.0;

        // Scale factor
        $scale = $deviceWidth / $designW;

        $layers = $templateJson['layers'] ?? $templateJson['objects'] ?? [];

        foreach ($layers as $layer) {
            $name = $layer['name'] ?? $layer['id'] ?? 'unknown';
            $type = $layer['type'] ?? 'unknown';

            $x = floatval($layer['x'] ?? 0);
            $y = floatval($layer['y'] ?? 0);
            $w = floatval($layer['w'] ?? $layer['width'] ?? 0);
            $h = floatval($layer['h'] ?? $layer['height'] ?? 0);
            $layerScaleX = floatval($layer['scaleX'] ?? 1);
            $layerScaleY = floatval($layer['scaleY'] ?? 1);
            $rawFontSize = floatval($layer['fontSize'] ?? $layer['font_size'] ?? $layer['size'] ?? 16);

            // === NATIVE RENDER FORMULAS ===
            // (matches editor_canvas_widget.dart build() + interactive_layer.dart)

            // Position
            $finalX = $x * $scale;
            $finalY = $y * $scale;

            // Dimensions (interactive_layer.dart Lines 77-84)
            $finalW = $w * $layerScaleX * $scale;
            $finalH = $h * $layerScaleY * $scale;

            // Font size (editor_canvas_widget.dart Lines 1060-1066)
            $finalFontSize = null;
            if ($type === 'text') {
                $finalFontSize = $rawFontSize * $ppiScale * $layerScaleY * $scale;

                // V1-V2 legacy Y offset (editor_canvas_widget.dart Line 935)
                if ($targetVersion < 3) {
                    $offsetMultiplier = 0.12; // Legacy factor
                    $finalY -= ($finalFontSize * $offsetMultiplier);
                }
            }

            $results[$name] = [
                'finalX' => round($finalX, 2),
                'finalY' => round($finalY, 2),
                'finalW' => round($finalW, 2),
                'finalH' => round($finalH, 2),
                'finalFontSize' => $finalFontSize !== null ? round($finalFontSize, 2) : null,
                'type' => $type,
            ];
        }

        return $results;
    }
}
```

### 7.4.3 `DualEngineValidator.php`

```php
<?php

namespace App\Services;

use App\Models\GoldenRender;

/**
 * Orchestrates Dual Engine Validation by comparing
 * Web + Native simulator results against Golden Snapshot baselines.
 */
class DualEngineValidator
{
    private WebRenderSimulator $webSim;
    private NativeRenderSimulator $nativeSim;

    // Tolerance thresholds (in pixels)
    const EXACT_MATCH_TOLERANCE = 0;     // 0px = perfect match
    const MINOR_DRIFT_TOLERANCE = 2;     // ≤2px = acceptable drift
    const MISMATCH_THRESHOLD = 2;        // >2px = mismatch (requires review)

    public function __construct()
    {
        $this->webSim = new WebRenderSimulator();
        $this->nativeSim = new NativeRenderSimulator();
    }

    /**
     * Validate a single frame's migration from currentVersion to targetVersion.
     *
     * @param  int    $frameId
     * @param  array  $templateJson  The frame's template JSON
     * @param  int    $currentVersion
     * @param  int    $targetVersion
     * @return array  Validation result
     */
    public function validate(int $frameId, array $templateJson, int $currentVersion, int $targetVersion): array
    {
        // Get Golden Baseline
        $golden = GoldenRender::where('frame_id', $frameId)
            ->where('render_version', $currentVersion)
            ->first();

        if (!$golden) {
            return [
                'status' => 'NO_BASELINE',
                'message' => "No golden baseline found for frame #$frameId at V$currentVersion",
                'web_mismatches' => [],
                'native_mismatches' => [],
            ];
        }

        // Compute new values at target version
        $newWeb = $this->webSim->compute($templateJson, $targetVersion);
        $newNative = $this->nativeSim->compute($templateJson, $targetVersion);

        // Compare against golden baselines
        $webMismatches = $this->compareComputed(
            $golden->web_computed ?? [],
            $newWeb,
            'web'
        );

        $nativeMismatches = $this->compareComputed(
            $golden->native_computed ?? [],
            $newNative,
            'native'
        );

        // Determine overall status
        $allMismatches = array_merge($webMismatches, $nativeMismatches);
        $maxDiff = 0;
        foreach ($allMismatches as $m) {
            $maxDiff = max($maxDiff, abs($m['diff']));
        }

        if (empty($allMismatches)) {
            $status = 'MATCH';
        } elseif ($maxDiff <= self::MINOR_DRIFT_TOLERANCE) {
            $status = 'MINOR_DRIFT';
        } else {
            $status = 'MISMATCH';
        }

        return [
            'status' => $status,
            'frame_id' => $frameId,
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
            'max_diff_px' => round($maxDiff, 2),
            'web_mismatches' => $webMismatches,
            'native_mismatches' => $nativeMismatches,
            'new_web_computed' => $newWeb,
            'new_native_computed' => $newNative,
        ];
    }

    /**
     * Compare golden computed values vs new computed values.
     */
    private function compareComputed(array $golden, array $newValues, string $engine): array
    {
        $mismatches = [];
        $propsToCompare = ['canvasX', 'canvasY', 'canvasW', 'canvasH', 'computedFontSize',
                           'finalX', 'finalY', 'finalW', 'finalH', 'finalFontSize'];

        foreach ($newValues as $layerName => $newProps) {
            $goldenProps = $golden[$layerName] ?? null;
            if ($goldenProps === null) continue; // New layer, no baseline to compare

            foreach ($propsToCompare as $prop) {
                if (!isset($newProps[$prop]) || !isset($goldenProps[$prop])) continue;

                $oldVal = floatval($goldenProps[$prop]);
                $newVal = floatval($newProps[$prop]);
                $diff = $newVal - $oldVal;

                if (abs($diff) > self::EXACT_MATCH_TOLERANCE) {
                    $mismatches[] = [
                        'engine' => $engine,
                        'layer' => $layerName,
                        'property' => $prop,
                        'golden_value' => $oldVal,
                        'new_value' => $newVal,
                        'diff' => round($diff, 2),
                        'severity' => abs($diff) <= self::MINOR_DRIFT_TOLERANCE ? 'minor' : 'major',
                        'auto_compensatable' => in_array($prop, ['canvasX','canvasY','canvasW','canvasH','finalX','finalY','finalW','finalH']),
                    ];
                }
            }
        }

        return $mismatches;
    }
}
```

### 7.4.4 Integrate into `PosterMakerController@bulkMigrateVersion()`

**File:** `app/Http/Controllers/Admin/PosterMakerController.php`
**Location:** Inside `bulkMigrateVersion()`, BEFORE the existing migration logic

Replace the current simple migration with a validation-first approach:

```php
// At the top of bulkMigrateVersion(), after validation (Line 901):
$validator = new \App\Services\DualEngineValidator();
$validationResults = [];
$autoCommitted = [];
$needsReview = [];

foreach ($request->ids as $id) {
    $frame = PosterMaker::find($id);
    if (!$frame) continue;

    $currentVersion = $frame->render_version ?? 1;
    $targetVersionInt = ($targetVersion !== 'none') ? (int)$targetVersion : $currentVersion;

    // Skip if already at target version
    if ($currentVersion >= $targetVersionInt) {
        $autoCommitted[] = ['id' => $id, 'name' => $frame->zip_name, 'status' => 'ALREADY_LATEST'];
        continue;
    }

    // Load template JSON
    $jsonPath = public_path('uploads/template/'.$frame->zip_name.'/json/'.$frame->zip_name.'.json');
    if (!file_exists($jsonPath)) {
        $errors[] = "Frame #{$id}: JSON file not found at {$jsonPath}";
        continue;
    }
    $json = json_decode(file_get_contents($jsonPath), true);
    if (!$json) {
        $errors[] = "Frame #{$id}: Invalid JSON";
        continue;
    }

    // Run Dual Engine Validation
    $result = $validator->validate($id, $json, $currentVersion, $targetVersionInt);
    $result['zip_name'] = $frame->zip_name;
    $validationResults[] = $result;

    if ($result['status'] === 'MATCH' || $result['status'] === 'MINOR_DRIFT') {
        // Safe to auto-commit
        // ... (existing migration logic: update JSON, DB, etc.) ...
        $autoCommitted[] = $result;
    } else {
        // Needs manual review
        $needsReview[] = $result;
    }
}

return response()->json([
    'success' => true,
    'total' => count($request->ids),
    'auto_committed' => count($autoCommitted),
    'needs_review' => count($needsReview),
    'auto_committed_frames' => $autoCommitted,
    'review_frames' => $needsReview,
    'errors' => $errors,
]);
```

---
---

# STEP 8: Structured Mismatch Review Popup {#step-8}

## 8.1 Files to Modify

| File | Path | What to Change |
|:---|:---|:---|
| `version_control.blade.php` | `resources/views/poster_maker/version_control.blade.php` | Add mismatch popup modal + update JS |

## 8.2 Add Mismatch Modal HTML (After existing content, ~Line 244)

```html
<!-- Migration Result Modal -->
<div id="migrationResultModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0;
    background:rgba(0,0,0,0.7); z-index:9999; overflow-y:auto; padding:40px;">
    <div style="max-width:900px; margin:0 auto; background:#1a1a2e; border-radius:16px;
        border:1px solid #333; font-family:'Poppins',sans-serif; color:#e0e0e0;">

        <!-- Header -->
        <div style="padding:20px 24px; border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center;">
            <h4 style="margin:0;">
                <i class="fas fa-code-compare" style="color:#4fc3f7;"></i>
                Version Migration Report
            </h4>
            <button onclick="closeMigrationModal()" style="background:none; border:none; color:#999; font-size:20px; cursor:pointer;">✕</button>
        </div>

        <!-- Summary Bar -->
        <div id="migrationSummary" style="padding:16px 24px; background:#162447; border-bottom:1px solid #333;">
            <!-- Populated by JS -->
        </div>

        <!-- Auto-Committed Section -->
        <div id="autoCommittedSection" style="padding:16px 24px; border-bottom:1px solid #222;">
            <h5 style="color:#66bb6a;">✅ Auto-Committed (<span id="autoCount">0</span>)</h5>
            <div id="autoCommittedList"><!-- Populated by JS --></div>
        </div>

        <!-- Needs Review Section -->
        <div id="needsReviewSection" style="padding:16px 24px;">
            <h5 style="color:#ffb74d;">⚠️ Needs Review (<span id="reviewCount">0</span>)</h5>
            <div id="reviewFramesList"><!-- Populated by JS --></div>
        </div>

        <!-- Footer -->
        <div style="padding:16px 24px; border-top:1px solid #333; text-align:right;">
            <button onclick="approveAllReviewed()" class="btn btn-success" id="btnApproveAll" style="display:none;">
                <i class="fas fa-check-double"></i> Approve & Commit All Reviewed
            </button>
            <button onclick="closeMigrationModal()" class="btn btn-outline-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>
```

## 8.3 Migration Result JavaScript

Replace the current `applyMigration()` success handler with:

```javascript
function showMigrationResult(data) {
    // Summary
    document.getElementById('migrationSummary').innerHTML = `
        <div style="display:flex; gap:24px; flex-wrap:wrap;">
            <div><strong>Total Frames:</strong> ${data.total}</div>
            <div style="color:#66bb6a;"><strong>Auto-Committed:</strong> ${data.auto_committed}</div>
            <div style="color:#ffb74d;"><strong>Needs Review:</strong> ${data.needs_review}</div>
            <div style="color:#ef5350;"><strong>Errors:</strong> ${data.errors?.length || 0}</div>
        </div>
    `;

    // Auto-committed list
    document.getElementById('autoCount').textContent = data.auto_committed;
    const autoList = document.getElementById('autoCommittedList');
    autoList.innerHTML = '';
    (data.auto_committed_frames || []).forEach(f => {
        autoList.innerHTML += `
            <div style="padding:8px; margin:4px 0; background:#1b3a2a; border-radius:8px; font-size:13px;">
                ✅ <strong>${f.zip_name || f.name || 'Frame #' + f.frame_id}</strong>
                — ${f.status} ${f.max_diff_px ? '(max drift: ' + f.max_diff_px + 'px)' : ''}
            </div>
        `;
    });

    // Needs review list
    document.getElementById('reviewCount').textContent = data.needs_review;
    const reviewList = document.getElementById('reviewFramesList');
    reviewList.innerHTML = '';
    window._reviewFrames = data.review_frames || [];

    if (window._reviewFrames.length > 0) {
        document.getElementById('btnApproveAll').style.display = 'inline-block';
    }

    window._reviewFrames.forEach((f, idx) => {
        let mismatchRows = '';
        const allMismatches = [...(f.web_mismatches || []), ...(f.native_mismatches || [])];
        allMismatches.forEach(m => {
            const color = m.severity === 'major' ? '#ef5350' : '#ffb74d';
            const autoTag = m.auto_compensatable ? '<span style="color:#4fc3f7; font-size:11px;">[Auto-Fix Available]</span>' : '';
            mismatchRows += `
                <tr>
                    <td style="color:#90caf9;">${m.engine.toUpperCase()}</td>
                    <td><strong>${m.layer}</strong></td>
                    <td><code>${m.property}</code></td>
                    <td style="color:#ef5350;">${m.golden_value}</td>
                    <td style="color:#66bb6a;">${m.new_value}</td>
                    <td style="color:${color};"><strong>${m.diff > 0 ? '+' : ''}${m.diff}px</strong></td>
                    <td>${autoTag}</td>
                </tr>
            `;
        });

        reviewList.innerHTML += `
            <div style="margin:12px 0; background:#2a1a1a; border:1px solid #4a2020; border-radius:12px; overflow:hidden;">
                <div style="padding:12px 16px; background:#3a1a1a; display:flex; justify-content:space-between; align-items:center;">
                    <span>
                        ⚠️ <strong>${f.zip_name || 'Frame #' + f.frame_id}</strong>
                        — V${f.current_version} → V${f.target_version}
                        <span style="color:#ef5350; font-size:12px;">(Max drift: ${f.max_diff_px}px)</span>
                    </span>
                    <div>
                        <button onclick="autoCompensateFrame(${idx})" class="btn btn-sm btn-outline-info" title="Auto-fix linear properties">
                            🔄 Auto-Compensate
                        </button>
                        <button onclick="approveFrame(${idx})" class="btn btn-sm btn-outline-success">
                            ✅ Approve
                        </button>
                    </div>
                </div>
                <div style="padding:0 16px 12px;">
                    <table style="width:100%; font-size:12px; color:#ccc;">
                        <thead>
                            <tr style="border-bottom:1px solid #444;">
                                <th>Engine</th><th>Layer</th><th>Property</th>
                                <th>Golden</th><th>New</th><th>Diff</th><th></th>
                            </tr>
                        </thead>
                        <tbody>${mismatchRows}</tbody>
                    </table>
                </div>
            </div>
        `;
    });

    document.getElementById('migrationResultModal').style.display = 'block';
}

function closeMigrationModal() {
    document.getElementById('migrationResultModal').style.display = 'none';
    location.reload();
}
```

## 8.4 Update `applyMigration()` AJAX Handler

Replace the existing success handler in `applyMigration()` (Lines 276-332):

```javascript
// In the fetch success handler, REPLACE:
//   alert(data.message);
//   location.reload();
// WITH:
showMigrationResult(data);
```

---
---

# STEP 9: Auto-Compensate Back-Calculation Engine {#step-9}

## 9.1 Logic

For simple linear properties (x, y, w, h), if the golden baseline value was `G` and the new version computes `N`, we can back-calculate the JSON value to make it compute `G` again:

```
Example:
- Golden: finalX = 10000 (at V4: x=200, native logic: 200 * 50 = 10000)
- New V5 native logic: x * 60 = 10000  →  x = 10000 / 60 = 166.67
- So we UPDATE the JSON's x from 200 to 166.67
```

## 9.2 Auto-Compensate API Endpoint

**File:** `app/Http/Controllers/Admin/PosterMakerController.php`

Add new method:

```php
/**
 * Auto-compensate a frame's JSON values to match golden baseline at new version.
 * Only works for simple linear properties (x, y, w, h).
 */
public function autoCompensate(Request $request)
{
    $request->validate([
        'frame_id' => 'required|integer',
        'target_version' => 'required|integer',
        'mismatches' => 'required|array',
    ]);

    $frame = PosterMaker::findOrFail($request->frame_id);
    $jsonPath = public_path('uploads/template/'.$frame->zip_name.'/json/'.$frame->zip_name.'.json');
    $json = json_decode(file_get_contents($jsonPath), true);

    $compensated = [];
    $manualRequired = [];

    foreach ($request->mismatches as $mismatch) {
        $layer = $mismatch['layer'];
        $property = $mismatch['property'];
        $goldenValue = floatval($mismatch['golden_value']);
        $newValue = floatval($mismatch['new_value']);

        // Only auto-compensate simple linear properties
        $linearProps = ['canvasX', 'canvasY', 'canvasW', 'canvasH', 'finalX', 'finalY', 'finalW', 'finalH'];

        if (!in_array($property, $linearProps)) {
            $manualRequired[] = $mismatch;
            continue;
        }

        // Find the layer in JSON and calculate correction factor
        $layers = &$json['layers'];
        foreach ($layers as &$jsonLayer) {
            $lName = $jsonLayer['name'] ?? $jsonLayer['id'] ?? '';
            if ($lName === $layer) {
                // Map computed property back to JSON property
                $jsonProp = $this->mapComputedToJsonProp($property);
                if ($jsonProp && isset($jsonLayer[$jsonProp]) && $newValue != 0) {
                    $currentJsonVal = floatval($jsonLayer[$jsonProp]);
                    $correctionFactor = $goldenValue / $newValue;
                    $correctedVal = round($currentJsonVal * $correctionFactor, 2);

                    $compensated[] = [
                        'layer' => $layer,
                        'property' => $jsonProp,
                        'old_json_value' => $currentJsonVal,
                        'new_json_value' => $correctedVal,
                        'correction_factor' => round($correctionFactor, 4),
                    ];

                    $jsonLayer[$jsonProp] = $correctedVal;
                }
                break;
            }
        }
    }

    // Save corrected JSON back
    file_put_contents($jsonPath, json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    // Update render_version
    $json['render_version'] = $request->target_version;
    $frame->render_version = $request->target_version;
    $frame->save();

    // Clear Redis cache
    \Cache::forget("template_json:{$frame->zip_name}");

    return response()->json([
        'success' => true,
        'compensated' => $compensated,
        'manual_required' => $manualRequired,
        'message' => count($compensated) . ' properties auto-compensated, ' . count($manualRequired) . ' need manual review',
    ]);
}

private function mapComputedToJsonProp(string $computedProp): ?string
{
    return match($computedProp) {
        'canvasX', 'finalX' => 'x',
        'canvasY', 'finalY' => 'y',
        'canvasW', 'finalW' => 'w',
        'canvasH', 'finalH' => 'h',
        'computedFontSize', 'finalFontSize' => null, // Font size is complex — manual review
        default => null,
    };
}
```

### 9.2.1 Add Route

```php
Route::post('Frame/auto-compensate', [PosterMakerController::class, 'autoCompensate'])
    ->name('admin.poster_maker.auto_compensate');
```

### 9.2.2 Frontend JS for Auto-Compensate Button

```javascript
function autoCompensateFrame(idx) {
    const frame = window._reviewFrames[idx];
    const allMismatches = [...(frame.web_mismatches || []), ...(frame.native_mismatches || [])];
    const autoFixable = allMismatches.filter(m => m.auto_compensatable);

    if (autoFixable.length === 0) {
        alert('No auto-compensatable properties found. Manual review required.');
        return;
    }

    if (!confirm(`Auto-compensate ${autoFixable.length} properties for "${frame.zip_name}"?`)) return;

    fetch("{{ route('admin.poster_maker.auto_compensate') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            frame_id: frame.frame_id,
            target_version: frame.target_version,
            mismatches: autoFixable,
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Remove from review list visually
            window._reviewFrames.splice(idx, 1);
            showMigrationResult({
                total: window._reviewFrames.length,
                auto_committed: 1,
                needs_review: window._reviewFrames.length,
                auto_committed_frames: data.compensated.map(c => ({
                    zip_name: frame.zip_name,
                    status: 'AUTO_COMPENSATED'
                })),
                review_frames: window._reviewFrames,
                errors: [],
            });
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(e => alert('Error: ' + e.message));
}
```

---
---

# STEP 10: Regression Test Admin Pages {#step-10}

## 10.1 Database Migration for Test Logs

```bash
php artisan make:migration create_regression_test_logs_table
```

```php
Schema::create('regression_test_logs', function (Blueprint $table) {
    $table->id();
    $table->string('trigger')->default('manual'); // 'deploy', 'manual', 'cron'
    $table->integer('total_frames_tested')->default(0);
    $table->integer('passed')->default(0);
    $table->integer('failed')->default(0);
    $table->longText('results')->nullable(); // JSON array of per-frame results
    $table->string('status')->default('running'); // 'running', 'completed', 'failed'
    $table->timestamps();
});
```

## 10.2 Model

**File:** `app/Models/RegressionTestLog.php`

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RegressionTestLog extends Model
{
    protected $fillable = ['trigger', 'total_frames_tested', 'passed', 'failed', 'results', 'status'];
    protected $casts = ['results' => 'array'];
}
```

## 10.3 Controller

**File:** `app/Http/Controllers/Admin/RegressionTestController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegressionTestLog;
use App\Models\GoldenRender;
use App\Models\PosterMaker;
use App\Services\NativeRenderSimulator;
use App\Services\WebRenderSimulator;
use App\Services\DualEngineValidator;
use Illuminate\Http\Request;

class RegressionTestController extends Controller
{
    /**
     * Show the regression test log page.
     */
    public function index()
    {
        $logs = RegressionTestLog::orderBy('id', 'desc')->paginate(20);
        $benchmarkFrames = PosterMaker::where('is_benchmark', true)->get();

        return view('admin.regression_tests.index', compact('logs', 'benchmarkFrames'));
    }

    /**
     * Run regression tests against all benchmark frames.
     */
    public function runTests(Request $request)
    {
        $benchmarkFrames = PosterMaker::where('is_benchmark', true)->get();

        if ($benchmarkFrames->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No benchmark frames found. Please mark some frames as benchmarks first.',
            ]);
        }

        $validator = new DualEngineValidator();
        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($benchmarkFrames as $frame) {
            $jsonPath = public_path('uploads/template/'.$frame->zip_name.'/json/'.$frame->zip_name.'.json');
            if (!file_exists($jsonPath)) {
                $results[] = [
                    'frame_id' => $frame->id,
                    'zip_name' => $frame->zip_name,
                    'status' => 'ERROR',
                    'message' => 'JSON file not found',
                ];
                $failed++;
                continue;
            }

            $json = json_decode(file_get_contents($jsonPath), true);
            $version = $frame->render_version ?? 1;

            // Validate current version against its own golden baseline
            // (checking if current code still produces the same results)
            $result = $validator->validate($frame->id, $json, $version, $version);
            $result['zip_name'] = $frame->zip_name;

            if ($result['status'] === 'MATCH') {
                $passed++;
                $result['test_result'] = 'PASSED';
            } else {
                $failed++;
                $result['test_result'] = 'FAILED';
            }

            $results[] = $result;
        }

        // Save test log
        $log = RegressionTestLog::create([
            'trigger' => $request->input('trigger', 'manual'),
            'total_frames_tested' => count($benchmarkFrames),
            'passed' => $passed,
            'failed' => $failed,
            'results' => $results,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
            'total' => count($benchmarkFrames),
            'passed' => $passed,
            'failed' => $failed,
            'results' => $results,
        ]);
    }

    /**
     * Show benchmark frames management page.
     */
    public function benchmarks()
    {
        $frames = PosterMaker::orderBy('id', 'desc')->paginate(50);
        $benchmarkIds = PosterMaker::where('is_benchmark', true)->pluck('id')->toArray();

        return view('admin.regression_tests.benchmarks', compact('frames', 'benchmarkIds'));
    }

    /**
     * Toggle benchmark status for a frame.
     */
    public function toggleBenchmark(Request $request)
    {
        $frame = PosterMaker::findOrFail($request->frame_id);
        $frame->is_benchmark = !$frame->is_benchmark;
        $frame->save();

        return response()->json([
            'success' => true,
            'is_benchmark' => $frame->is_benchmark,
        ]);
    }
}
```

## 10.4 Add `is_benchmark` Column to `poster_maker` Table

```bash
php artisan make:migration add_is_benchmark_to_poster_maker
```

```php
Schema::table('poster_maker', function (Blueprint $table) {
    $table->boolean('is_benchmark')->default(false)->after('render_version');
});
```

## 10.5 Routes

**File:** `routes/web.php` (inside admin group)

```php
Route::get('regression-test-log', [RegressionTestController::class, 'index'])->name('admin.regression_tests.index');
Route::post('regression-test-run', [RegressionTestController::class, 'runTests'])->name('admin.regression_tests.run');
Route::get('benchmark-frames', [RegressionTestController::class, 'benchmarks'])->name('admin.regression_tests.benchmarks');
Route::post('benchmark-toggle', [RegressionTestController::class, 'toggleBenchmark'])->name('admin.regression_tests.toggle_benchmark');
```

## 10.6 Add to Admin Sidebar

Find the admin sidebar file and add after the Frame section:

```html
<!-- Regression Tests -->
<li class="nav-item">
    <a class="nav-link {{ request()->is('admin/regression-test-log*') ? 'active' : '' }}"
       href="{{ route('admin.regression_tests.index') }}">
        <i class="fas fa-flask"></i> 🧪 Regression Tests
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->is('admin/benchmark-frames*') ? 'active' : '' }}"
       href="{{ route('admin.regression_tests.benchmarks') }}">
        <i class="fas fa-bookmark"></i> Benchmark Frames
    </a>
</li>
```

---
---

# STEP 11: Benchmark Control Frames System {#step-11}

## 11.1 What Are Benchmark Frames?

These are 5-10 permanent test frames that cover all major rendering scenarios:

| # | Frame Name | What It Tests |
|:---|:---|:---|
| 1 | `benchmark_point_text` | Single-line point text, FittedBox scaling |
| 2 | `benchmark_paragraph_text` | Multi-line paragraph text, line-height |
| 3 | `benchmark_psd_mask` | PSD clipping mask auto-detection + ImageShader |
| 4 | `benchmark_vector_shapes` | Rect, Circle, Triangle, Line (V4+ native draw) |
| 5 | `benchmark_gradient_text` | Text with LinearGradient fill (ShaderMask) |
| 6 | `benchmark_icon_layer` | FontAwesome icon rendering |
| 7 | `benchmark_mixed_layout` | All types together in one template |
| 8 | `benchmark_business_frame` | Business frame with logo, phone, email overlays |
| 9 | `benchmark_high_ppi` | Template with PPI != 72 (tests ppiScale) |
| 10 | `benchmark_legacy_v1` | V1 frame (tests legacy fallback code paths) |

## 11.2 How to Create Benchmark Frames

1. Create each frame normally in the Web Editor with the specific features above
2. Publish each frame → Golden Baseline automatically captured (Step 5)
3. Open each frame in the Native Editor → Native Golden automatically sent (Step 5.4)
4. Go to Admin → Benchmark Frames → Toggle "Benchmark" ON for each test frame
5. These frames are now permanently protected and tested on every regression run

## 11.3 Regression Test Workflow

```
Developer makes code change → Deploys to staging
                               │
                               ▼
Admin goes to /admin/regression-test-log
                               │
                               ▼
Clicks "Run Regression Tests" button
                               │
                               ▼
System loops through all is_benchmark=true frames:
  For each benchmark frame:
    1. Load its JSON
    2. Compute Web values using current WebRenderSimulator
    3. Compute Native values using current NativeRenderSimulator
    4. Compare vs golden_renders baseline
    5. Log PASS/FAIL per layer per property
                               │
                               ▼
Results displayed:
  ✅ benchmark_point_text: PASSED (all values match)
  ✅ benchmark_vector_shapes: PASSED
  ❌ benchmark_paragraph_text: FAILED
     └── Layer "subtitle": finalY golden=5000, current=4992 (diff=-8px)
     └── Layer "body": finalH golden=300, current=312 (diff=+12px)
  ✅ benchmark_psd_mask: PASSED
  ...

Developer sees failure → Knows exactly which layer/property broke → Fixes code
```

---
---

# 📋 IMPLEMENTATION PRIORITY ORDER

| Priority | Step | Dependencies | Estimated Time |
|:---|:---|:---|:---|
| **P0** | Step 1: Direct API JSON Serving | None | 2-3 hours |
| **P0** | Step 2: Mobile Hybrid Renderer | Step 1 | 3-4 hours |
| **P1** | Step 3: Redis Cache + ETag | Step 1 | 2-3 hours |
| **P1** | Step 4: Batch API | Step 1 | 1-2 hours |
| **P2** | Step 5: Golden Snapshot DB + Capture | None | 3-4 hours |
| **P2** | Step 6: Web Editor Diff Modal | None | 3-4 hours |
| **P3** | Step 7: Dual Engine Validators | Step 5 | 4-5 hours |
| **P3** | Step 8: Mismatch Popup UI | Step 7 | 2-3 hours |
| **P3** | Step 9: Auto-Compensate | Step 7, Step 8 | 2-3 hours |
| **P4** | Step 10: Regression Test Pages | Step 5, Step 7 | 3-4 hours |
| **P4** | Step 11: Benchmark Frames | Step 10 | 1-2 hours |

**Total estimated: ~30-40 hours of focused development**

---

# ⚠️ CRITICAL REMINDERS FOR ANY IMPLEMENTING AI/DEVELOPER

1. **NEVER modify locked functions** — ALL locked code sections in AGENTS.md require password `Brijesh@1415`
2. **NEVER modify existing version code paths** — Always create `_functionNameVN()` copies
3. **ALWAYS use `if (renderVersion >= N)` blocks** — Never let new logic affect old version frames
4. **ALWAYS invalidate Redis cache** after ANY change to template JSON
5. **ALWAYS run regression tests** after ANY code change to rendering files
6. **The `schema_json`/`legacy_json` fields in API are NOT used yet** — The API serves raw `"json"` field containing the legacy JSON string
7. **`CachedNetworkImage` is ALREADY in pubspec.yaml** — No new package installation needed
8. **`layers_json` DB column ALREADY exists** in `PosterMaker` model — It stores the complete legacy JSON
9. **`CURRENT_RENDER_VERSION = 4`** is at Line 23 of `template_builder.js`
10. **`currentMaxVersion` in version_control.blade.php is HARDCODED to `3`** (Line 879 of PosterMakerController) — This needs to be updated to match `CURRENT_RENDER_VERSION`

---

*Note: Any modifications to LOCKED sections (e.g. `render_version`, Web Editor rendering, PSD clipping mask, Native Editor image sizing) will strictly follow the Workspace Rules (`AGENTS.md`) and ask for passwords (`Brijesh@1415`) before code changes.*
