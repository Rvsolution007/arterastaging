# 🚀 Native Mobile x Web Editor: High-Scale Hybrid Architecture & Roadmap

**File:** `nativexwebxeditor.md`  
**Created Date:** 14 July 2026  
**Status:** Architectural Blueprint & Implementation Plan (`Abhi Baaki Hai` Roadmap)  
**Target Architecture:** Web Editor (Template Builder) ↔ Laravel API ↔ Flutter Native Mobile App  

---

## 📋 Executive Summary

Is document me humne **Hybrid Architecture (Direct API JSON Serving + Asset Caching + Redis Edge Caching + Diff Review Modal)** ka complete technical roadmap, architecture breakdown, aur **CDN & Redis Memory ka Free vs Paid Cost Analysis** detail me document kiya hai.

---

## ⏳ What is Yet to be Developed (`Abhi Baaki Hai`)

### 📌 Phase 1: Direct API JSON Serving & Hybrid Asset Caching
* **Current Bottleneck:** Abhi Mobile App server se `.zip` archive (`Template_UUID.zip`) download karke phone ki physical storage (`app_docs/frames/...`) me extract/unzip karta hai, jisme CPU overhead aur disk space consume hota hai.
* **Proposed Solution:**
  1. **Direct API Serving (`HomeApi.php`)**: API ab `schema_json` aur `legacy_json` directly JSON response me serve karega. Har layer ke exact coordinates (`x, y`), dimensions (`w, h`), `render_version`, aur Vector Shapes/Icons RAM me instant load honge.
  2. **Intelligent Image Caching (`CachedNetworkImage`)**: Jin layers me `type: 'image'` hai, unka URL `CachedNetworkImageProvider` / `DefaultCacheManager` handle karega. Pehli baar download hokar phone ke `/cache/` folder me save hoga, aur agli baar **0ms latency** ke sath physical cache se load hoga.
  3. **Pre-Export Safety Check (`_checkAssetsReady()`)**: High-Resolution Poster Export (`boundary.toImage(pixelRatio: 3.0)`) chalane se pehle system automatically check karega ki saari network images cache se render ho chuki hain ya nahi. Isse export kabhi blank/black ya half-loaded nahi aayega!

---

### 📌 Phase 2: High-Scale Engine (Redis Edge Caching & Smart Sync)
Lakhon users jab festival subah ek sath app kholenge, to MySQL Database crash na ho iske liye **3 Enterprise-Level Request Patterns** lagayenge:

#### 1. Redis In-Memory Caching (Zero Database Query on Read)
* **Backend Logic:** Jab Admin Web Editor me **Publish** press karega (`saveFrame` / `save`), Laravel `schema_json` ko MySQL table (`editor_templates`) me save karne ke sath **Redis Cache** me write kar देगा (`key: template_json_{UUID}`).
* **Read Logic:** Mobile API hits par Laravel direct Redis se `5ms` me JSON return karega. MySQL query execution zero ho jayega (`SELECT` queries = 0).

#### 2. ETag / `last_updated` Conditional Request (Smart Sync)
* **Problem:** Baar-baar same 20KB JSON download karne se network bandwidth waste hoti hai.
* **Solution:** Mobile App pehli baar JSON download karke apne SQLite/Hive DB me rakh lega (`uuid` + `updated_at`). Agli baar request me `last_updated` timestamp bhejega:
  ```http
  GET /api/template/{uuid}?last_updated=2026-07-14_18:00:00
  ```
* Agar Admin ne koi update nahi kiya hai, to server `HTTP 304 Not Modified` (0 Bytes payload) return karega aur app local copy use kar lega. Agar update hua hai tabhi naya JSON aayega.

#### 3. Bundled Batch API (`Single Request Payload`)
* Festival Detail List ya Category List par 20 alag-alag API calls ki jagah ek **Single Bundled Batch API** (`GET /api/templates/batch?uuids=id1,id2,id3`) top 10 templates ki `schema_json` ek hi fast JSON payload me deliver karega.

---

### 📌 Phase 3: Web Editor One-Click Upgrade & Diff Review Modal
* **Problem:** Agar kisi old template (`render_version: 1` ya `2`) ko one-click se latest `render_version: 4` me upgrade karein, to alignment ya text dimensions me minor shifts aa sakte hain.
* **Proposed Solution (Diff Review System):**
  1. **Diff Engine:** Save click karte hi system old JSON (`v1`) aur new JSON (`v4`) ko compare karega.
  2. **Diff Review Modal:** Ek popup modal exact changes show karega:
     * *Layer `phone_num`: Y-Coordinate shifted from `920px` → `908px` (Y-Offset Normalization applied)*
     * *Layer `shape_rect`: Upgraded from Raster PNG (`45 KB`) → Vector Shape (`fill: #1A73E8, rx: 16`)*
     * *Layer `title_text`: Width pre-baked from `0 (Auto)` → `480px`*
  3. **Side-by-Side Visual Preview Toggle:** User `[Old Version v1] ↔ [New Version v4]` toggle karke canvas par preview dekh sakega.
  4. **Safe Commit & Rollback:** **"Approve & Publish"** press karne par hi DB me commit hoga aur purana JSON automatically `template_revisions` table me backup ho jayega jisse 1-click **Rollback** possible rahega.

---

## 💰 Detailed Cost Analysis: CDN & Redis Memory (Free vs Paid Tiers)

Aapka sawal tha ki **CDN aur Redis Memory FREE hota hai ya PAID?** Niche exact breakdown, free limits, aur real-world costs di gayi hain:

### 1. Redis In-Memory Cache (RAM Caching)

#### 🟢 Option A: Self-Hosted on Your Existing Server (100% FREE! ⭐ Recommended)
* **What it is:** Redis ek Open-Source / BSD-licensed software hai.
* **Cost:** **₹0 / $0 (Totally FREE)**. Agar aapka Laravel backend ek VPS (DigitalOcean Droplet, AWS EC2, ya Dedicated Linux Server) par chal raha hai jisme RAM available hai, to hum usi server me `sudo apt install redis-server` karke Redis chala sakte hain!
* **Memory Capacity & Math:**
  * Ek template ka `schema_json` takriban **`10 KB` se `15 KB`** ka hota hai.
  * Agar hum **`10,000` (10 Hazaar) templates** ko Redis RAM me cache karein:
    $$\text{Total RAM Required} = 10,000 \times 15\text{ KB} = 150,000\text{ KB} \approx \mathbf{150\text{ MB RAM}}$$
  * Agar aapke server me **`1 GB RAM`** free hai, to usme **`66,000+` templates** ek sath cache ho sakte hain! Iske liye koi alag monthly fee dene ki zaroorat nahi hai.

#### 🟡 Option B: Cloud Managed Redis (Serverless / Managed)
Agar aap chahte hain ki Redis ka alag cloud server ho jo automatic scaling aur backups sambhale:
* **Upstash Redis (Serverless Cloud - Free Tier Available)**:
  * **Free Tier:** Daily `10,000` requests + `256 MB` RAM storage **100% FREE** (No credit card required for testing).
  * **Paid Tier:** Pay-as-you-go (`$0.20` per 100K requests).
* **DigitalOcean Managed Redis**:
  * **Paid Tier:** `$15 / month` (approx ₹1,250/month) for `1 GB RAM` dedicated node with auto-failover.
* **AWS ElastiCache for Redis**:
  * **Paid Tier:** Approx `$15 - $20 / month` for micro/small instance.

---

### 2. CDN (Content Delivery Network - For Images & Assets)

#### 🟢 Option A: Cloudflare CDN Proxy (100% FREE for Bandwidth! ⭐ Recommended)
* **What it is:** Cloudflare ka standard web CDN (`Orange Cloud proxy`).
* **Cost:** **₹0 / $0 (Totally FREE)**. Cloudflare Free Plan me:
  * **Unlimited Bandwidth & Outbound Data Transfer!**
  * Fast DNS routing + Global CDN caching.
  * API JSON aur image assets (`.png, .jpg, .webp`) ko CDN edge par automatically cache karta hai (`Cache-Control: public, max-age=31536000`).
* **Why use this:** Aapke existing server se image loading load 95% tak Cloudflare ke free global edge servers par shift ho jata hai!

#### 🟡 Option B: Object Storage CDN (DigitalOcean Spaces / AWS S3 + CloudFront)
Currently aapka project **DigitalOcean Spaces** (`StorageSetting::getStorageSetting('storage') == 'DigitalOcean'`) support karta hai:
* **DigitalOcean Spaces CDN**:
  * **Cost:** `$5 / month` (approx ₹420/month).
  * **Includes:** `250 GB` physical storage + **`1 TB` (1,000 GB) Outbound CDN Bandwidth per month**.
  * **Additional Bandwidth:** Only `$0.01` per GB (₹0.80 per GB) after 1 TB limit.
* **AWS S3 + CloudFront CDN**:
  * **Free Tier:** First `1 TB` outbound transfer per month is now **FREE** on CloudFront! Storage is `$0.023` per GB/month.

---

### 📊 Cost Summary Table

| Service / Tool | Free Tier / Option | Paid Tier / Enterprise Option | Recommended For Artera |
| :--- | :--- | :--- | :--- |
| **Redis Cache** | **₹0 / $0** (Install locally on existing Linux VPS/Server RAM) | **$15/month** (DigitalOcean Managed Redis 1GB node) | **Self-Hosted Free Redis** on current server (uses just 150MB RAM for 10K frames) |
| **CDN (Edge Caching)** | **₹0 / $0** (Cloudflare Free Plan with Unlimited Bandwidth) | **$5/month** (DigitalOcean Spaces 250GB + 1TB CDN transfer) | **DigitalOcean Spaces ($5/mo) + Cloudflare Free Proxy** for double-edge speed |

---

## 🛠️ Next Steps Checklist (Action Plan)

Jab bhi aap batayein ki hume aage shuru karna hai, hum niche diye gaye sequence me step-by-step implementation start karenge:

- [ ] **Step 1: Direct API JSON Serving (`HomeApi.php`)**
  - `HomeApi.php` ko modify karke `schema_json` aur `render_version` directly API response me expose karna without requiring ZIP download.
- [ ] **Step 2: Mobile App Hybrid Renderer & Asset Caching (`editor_canvas_widget.dart`)**
  - ZIP extraction logic ko bypass karke direct `schema_json` parse karna.
  - Image layers (`type: 'image'`) ke liye `CachedNetworkImage` + Disk Cache Manager integrate karna.
  - `_checkAssetsReady()` safety check add karna for `3.0x` Ultra HD Export (`boundary.toImage`).
- [ ] **Step 3: High-Scale Optimizations (Redis & `304 Not Modified`)**
  - Laravel me Redis cache layer connect karna (`template_json_{UUID}`).
  - `last_updated` conditional sync (`ETag` / `HTTP 304`) API header management in Flutter.
- [ ] **Step 4: Web Editor Diff Review Modal (`template_builder.js`)**
  - One-Click Version Upgrade Diff calculation engine.
  - Side-by-Side preview toggle modal UI before `saveFrame`.

---
*Note: Any modifications to LOCKED sections (e.g. `render_version`, Web Editor rendering, PSD clipping mask, Native Editor image sizing) will strictly follow the Workspace Rules (`AGENTS.md`) and ask for passwords (`Brijesh@1415`) before code changes.*

---
---

# 🛡️ Phase 4–7: Golden Snapshot, Dual Engine Validation, Version Migration Safety & Regression Protection

**Added:** 14 July 2026  
**Context:** Is section me humne **Web Editor + Native Editor dono ka versioned rendering logic**, **Golden Snapshot baseline system**, **Version Control Dashboard se bulk migration ke waqt Dual Engine Validation**, **Structured Mismatch Popup**, aur **Regression Bug Protection** ka complete engineering blueprint document kiya hai.

---

## 📌 Phase 4: Render Version — Dono Editors Ka Logic Connect Karna

### Current State (Kya hai abhi):
Abhi `render_version` sirf **Web Editor ke export formula** (`template_builder.js` me `exportArteraSchema` / `exportLegacyJson`) ko track karta hai. Native Editor (`editor_canvas_widget.dart`) me `if (renderVersion >= N)` se version-specific rendering code paths hain, lekin **dono editors ka version tracking ek unified system se connected nahi hai**.

### Proposed Architecture (Kya banana hai):
`render_version` ko ek **Unified Cross-Platform Version Contract** banana hai jo **Web Editor ke export logic** aur **Native Editor ke rendering logic** dono ko ek sath track kare.

#### How Version Isolation Works (Both Editors):

**Web Editor (`template_builder.js`) — Export Side:**
```javascript
// Version 4 export formula (FROZEN — never modify)
if (CURRENT_RENDER_VERSION >= 4) {
    // V4: Shapes exported as vector data, dimensions baked
    o.fill = fillColor;
    o.rx = obj.rx;
    o.ry = obj.ry;
}

// Version 5 export formula (NEW — added later)
if (CURRENT_RENDER_VERSION >= 5) {
    // V5: New line-height baking, new text offset calculation
    o.lineHeightBaked = obj.lineHeight * obj.scaleY;
}
```

**Native Editor (`editor_canvas_widget.dart`) — Render Side:**
```dart
if (renderVersion >= 5) {
    // V5 Native Logic (NEW — only for V5+ frames)
    finalH = layer['h'] * scale + newFeaturePadding;
} else if (renderVersion >= 4) {
    // V4 Native Logic (FROZEN — never modify)
    finalH = layer['h'] * scale;
} else {
    // V1-3 Legacy Logic (FROZEN)
    finalH = calculateLegacyHeight(layer, scale);
}
```

**Golden Rule:** Jab bhi naya version introduce ho:
1. `CURRENT_RENDER_VERSION` increment karo (Web Editor)
2. Web Editor me naya export formula `if (version >= N)` me likho
3. Native Editor me naya rendering logic `if (renderVersion >= N)` me likho
4. **Purane version ke code blocks ko KABHI modify mat karo!**

---

## 📌 Phase 5: Golden Snapshot Baseline System

### Concept:
Jab koi frame pehli baar publish hoke perfectly render ho rahi ho (Web + Native dono me), tab system us frame ka ek **"Golden Reference Baseline"** capture karke permanently save karega. Yeh baseline future version migrations me comparison ke liye use hoga.

### Kya Capture Hoga (Dual Snapshot):

#### 🟢 A. Native Editor Golden Baseline:
Har layer ki **final computed pixel values** jo Native Editor (`editor_canvas_widget.dart` + `interactive_layer.dart`) calculate karta hai:
* `finalX` = `layer['x'] * scale`
* `finalY` = `layer['y'] * scale` (with Y-offset adjustments)
* `finalW` = `layer['w'] * scale`
* `finalH` = `layer['h'] * scale`
* `finalFontSize` = `rawSize * ppiScale * layerScaleY * scale`
* `isSingleLine` = `true/false`
* Text alignment computed values
* Optional: `native_snapshot.png` (pixel-perfect rendered screenshot)

#### 🔵 B. Web Editor Golden Baseline:
Har layer ki **canvas computed values** jo Web Editor (`template_builder.js`) ke `_doRender()` aur export functions calculate karte hain:
* `canvasX` = `aCoords.tl.x` (absolute top-left X after `setCoords()`)
* `canvasY` = `aCoords.tl.y` (absolute top-left Y)
* `canvasW` = `Math.round(obj.width * obj.scaleX)` (baked width)
* `canvasH` = `Math.round(obj.height * obj.scaleY)` (baked height)
* `computedFontSize` = `obj.fontSize * Math.abs(obj.scaleY)` (baked font size)
* `fillColor` (hex string ya gradient object)
* `fontFamily`, `fontWeight`, `fontStyle`, `textAlign`
* Web thumbnail: `web_thumbnail.webp` (canvas.toDataURL() screenshot)

### Database Storage (`golden_renders` Table):
```
golden_renders table:
┌──────────┬─────────┬────────────────────────────┬────────────────────────────┬──────────────────┐
│ frame_id │ version │ native_computed (JSON)      │ web_computed (JSON)        │ created_at       │
├──────────┼─────────┼────────────────────────────┼────────────────────────────┼──────────────────┤
│ 123      │ 4       │ {"text_1": {               │ {"text_1": {               │ 2026-07-14       │
│          │         │   "finalX": 10000,         │   "canvasX": 210,          │                  │
│          │         │   "finalY": 5000,          │   "canvasY": 150,          │                  │
│          │         │   "finalW": 300,           │   "canvasW": 480,          │                  │
│          │         │   "finalFontSize": 48,     │   "computedFontSize": 48,  │                  │
│          │         │   "isSingleLine": true},   │   "fontFamily": "Inter",   │                  │
│          │         │  "icon_phone": {           │   "textAlign": "center"},  │                  │
│          │         │   "finalX": 6000,          │  "icon_phone": {           │                  │
│          │         │   "finalY": 8000,          │   "canvasX": 100,          │                  │
│          │         │   "finalW": 150,           │   "canvasY": 800,          │                  │
│          │         │   "finalH": 150}}          │   "canvasW": 50,           │                  │
│          │         │                            │   "canvasH": 50}}          │                  │
│          │         │ + native_snapshot.png       │ + web_thumbnail.webp       │                  │
├──────────┼─────────┼────────────────────────────┼────────────────────────────┼──────────────────┤
│ 124      │ 4       │ {...}                      │ {...}                      │ 2026-07-14       │
└──────────┴─────────┴────────────────────────────┴────────────────────────────┴──────────────────┘
```

### Kab Capture Hoga:
1. **Web Editor me Publish** karte waqt: Web computed values + thumbnail automatically capture honge.
2. **Native Editor first successful render** ke baad: Native computed values capture honge (via a debug/diagnostic API call ya background sync).
3. **Version Dashboard se successful migration** ke baad: Naye version ke computed values as new golden baseline save honge.

---

## 📌 Phase 6: Version Control Dashboard — Dual Engine Bulk Migration with Mismatch Popup

### Concept:
Jab Admin **Version Control Dashboard** (`/admin/Frame/version-control`) se multiple frames select karke **"Apply Migration"** (e.g., V1 → V4, ya V4 → V5) click kare, tab system **blindly DB me overwrite nahi karega!** Pehle ek **Dual Engine Validation** chalegi jo Web + Native dono sides ke naye computed values ko purane Golden Baseline se compare karegi.

### Complete Migration Flow:

```
Admin clicks "Apply Migration" (V4 → V5, 5 frames selected)
│
│ ┌─── PHASE A: Background Simulation ───────────────────────────────┐
│ │ For each selected frame:                                         │
│ │   1. Load frame's legacy_json / schema_json from DB              │
│ │   2. Load Golden Baseline from golden_renders table              │
│ │   3. Run V5 Web Export Formulas (server-side math recompute)     │
│ │   4. Run V5 Native Render Formulas (server-side math recompute)  │
│ │   5. Compare new computed values vs golden baseline               │
│ │   6. Categorize result: MATCH / MINOR_DRIFT / MISMATCH           │
│ └──────────────────────────────────────────────────────────────────┘
│
├── Frame #1: Dual Engine Check
│   ├── Native Re-Compute: V5 native logic → compare vs native golden
│   │   └── ✅ MATCH (all layers within 2px tolerance)
│   ├── Web Re-Compute: V5 web formula → compare vs web golden
│   │   └── ✅ MATCH
│   └── RESULT: ✅ AUTO-COMMIT (both sides safe, silently migrate)
│
├── Frame #2: Dual Engine Check
│   ├── Native Re-Compute: ✅ MATCH
│   ├── Web Re-Compute: ❌ MISMATCH
│   │   └── Layer "text_title": computedFontSize was 48, now 52
│   └── RESULT: ⚠️ HOLD — Show in Mismatch Popup
│
├── Frame #3: Dual Engine Check
│   ├── Native Re-Compute: ❌ MISMATCH
│   │   └── Layer "icon_phone": finalX was 10000, now 12000
│   ├── Web Re-Compute: ✅ MATCH
│   └── RESULT: ⚠️ HOLD — Show in Mismatch Popup
│
├── Frame #4: ✅✅ AUTO-COMMIT
└── Frame #5: ✅✅ AUTO-COMMIT
```

### 3 Possible Outcomes Per Frame:

| Outcome | Condition | System Action |
|:---|:---|:---|
| ✅ **MATCH** | All layers' computed values identical (within 2px tolerance) on both Web & Native | Auto-commit migration to DB silently |
| ⚠️ **MINOR DRIFT** | Difference between 2px–5px (rounding artifacts) | Auto-commit + Log warning in migration report |
| ❌ **MISMATCH** | Any layer difference > 5px on either Web or Native side | **HOLD** the frame — Show in Mismatch Review Popup |

### Structured Mismatch Review Popup (Detailed UI):

Jab migration complete ho aur kuch frames me mismatch aaye, to ek **responsive, well-structured, data-rich popup modal** aayega:

```
┌────────────────────────────────────────────────────────────────────────────────┐
│ ⚠️  VERSION MIGRATION REPORT                                                  │
│ Migration: V4 → V5  |  Total: 5 frames  |  ✅ Auto-Committed: 3  |  ⚠️ Review: 2 │
├────────────────────────────────────────────────────────────────────────────────┤
│                                                                                │
│ 📦 FRAME: "Festival_Diwali_2026" (Frame_PEMA_0818_1)                           │
│    Current Version: V4  →  Target Version: V5                                  │
│ ┌──────────────────────────────────────────────────────────────────────────┐   │
│ │ ❌ WEB EDITOR MISMATCH                                                  │   │
│ │                                                                          │   │
│ │  Layer Name       │ Property       │ Old Value  │ New Value  │ Diff      │   │
│ │ ──────────────────┼────────────────┼────────────┼────────────┼────────── │   │
│ │  text_title       │ fontSize       │ 48         │ 52         │ +4        │   │
│ │  text_title       │ canvasY        │ 150        │ 142        │ -8px      │   │
│ │  shape_bg_box     │ width          │ 480        │ 510        │ +30px     │   │
│ │  shape_bg_box     │ rx (radius)    │ 16         │ 16         │ ✅ same    │   │
│ │  icon_phone       │ canvasX        │ 100        │ 100        │ ✅ same    │   │
│ └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                │
│   [ ✅ Approve & Commit ]  [ 🔄 Auto-Compensate Values ]  [ ✏️ Open in Editor ] │
│                                                                                │
├────────────────────────────────────────────────────────────────────────────────┤
│                                                                                │
│ 📦 FRAME: "Business_Card_Pro" (Frame_PEMA_2000_2)                              │
│    Current Version: V4  →  Target Version: V5                                  │
│ ┌──────────────────────────────────────────────────────────────────────────┐   │
│ │ ❌ NATIVE EDITOR MISMATCH                                               │   │
│ │                                                                          │   │
│ │  Layer Name       │ Property       │ Old Value  │ New Value  │ Diff      │   │
│ │ ──────────────────┼────────────────┼────────────┼────────────┼────────── │   │
│ │  icon_phone       │ finalX         │ 10000      │ 12000      │ +2000px   │   │
│ │  text_website     │ finalFontSize  │ 36         │ 38.4       │ +2.4      │   │
│ │  text_phone       │ finalY         │ 5000       │ 5000       │ ✅ same    │   │
│ └──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                │
│   [ ✅ Approve & Commit ]  [ 🔄 Auto-Compensate Values ]  [ ✏️ Open in Editor ] │
│                                                                                │
├────────────────────────────────────────────────────────────────────────────────┤
│                                                                                │
│ ✅ AUTO-COMMITTED FRAMES (No Issues):                                          │
│    • Festival_Holi_2026 (Frame_PEMA_0818_3) — All layers matched ✅             │
│    • Wedding_Invite_Gold (Frame_PEMA_0818_2) — All layers matched ✅            │
│    • Raksha_Bandhan (Frame_PEMA_1110_1) — All layers matched (minor drift) ✅   │
│                                                                                │
└────────────────────────────────────────────────────────────────────────────────┘
```

### Popup Action Buttons (Per Mismatched Frame):

| Button | Action |
|:---|:---|
| **✅ Approve & Commit** | Admin review karke confirm karega ki shift acceptable hai (e.g., `+4px` font size is okay). Frame DB me V5 ke sath commit ho jayega. New golden baseline save hoga. |
| **🔄 Auto-Compensate Values** | System **simple linear properties** (`x, y, w, h`) ka back-calculation karega taki final pixel output golden baseline se match kare. Example: `x` was `200`, native logic changed `×50` → `×60`, system `x` ko `200` se `166.66` kar dega taki `166.66 × 60 = 10000` (same as old `200 × 50 = 10000`). **Important:** Auto-compensate sirf simple linear properties par apply hoga. Complex/interdependent properties (jaise `fontSize` jo `y` ko affect kare) par nahi — unke liye manual editor fix recommend hoga. |
| **✏️ Open in Editor** | Frame directly Web Editor me open ho jayega jahan admin visually check karke manually adjust aur publish kar sakta hai. |

### Auto-Compensate Back-Calculation Math:
Simple linear properties ke liye formula:
$$x_{compensated} = \frac{\text{Golden Final Value (old)}}{\text{New Version Logic Multiplier}}$$

Example:
* Golden baseline: `icon_phone.finalX = 10000` (computed with old logic `x × 50`)
* New V5 logic: `x × 60`
* Back-calculate: $x_{new} = \frac{10000}{60} = 166.66$
* Save `x = 166.66` in JSON → New logic: `166.66 × 60 = 10000` ✅ Matches golden!

**Auto-Compensate Limitations (Safety Rules):**
* ✅ Apply on: `x`, `y`, `w`, `h` (simple multiply/add formulas)
* ❌ Do NOT auto-compensate: `fontSize` (affects `y` offset → cascade), `lineHeight` (affects text box height → cascade), `gradient coordinates` (complex object), `polygon points` (array of interdependent coordinates)
* For non-compensable properties, popup will show: *"⚠️ Manual review required — this property has cascade dependencies"*

### Important Rule: Manual Edit + Publish me Auto-Compensate NAHI Lagega:
Yeh Dual Engine Validation aur Auto-Compensate logic **SIRF Version Control Dashboard ke "Apply Migration" button** par chalega! Agar admin manually koi old V4 frame Web Editor me open karke thode changes kare aur Publish kare, to:
* Frame latest `CURRENT_RENDER_VERSION` ke sath save hoga (normal behavior)
* Koi auto-compensation nahi lagegi
* Kyunki admin khud manually changes kar raha hai, wo apni responsibility par publish kar raha hai

---

## 📌 Phase 7: Regression Bug Protection System

### Problem:
Software me sabse common aur dangerous bug type **"Regression Bug"** hai — jab ek bug fix karte waqt ya naye feature add karte waqt **anjane me koi dusra chalta hua logic toot jaye**.

Example scenario:
* Developer ne Version 5 me text baseline ka ek bug fix kiya
* Fix karne me usne `_buildText()` function me ek line change ki
* Lekin usi function ko **Image masking** aur **Shape border rendering** bhi internally use karti thi
* Result: Text fix ho gaya ✅, lekin Image mask aur Shape border 2 jagah toot gaye ❌❌

### Solution: 3-Layer Regression Protection System:

#### 🟢 Layer 1: Immutable Version-Isolated Helpers (Code Architecture Rule)
Jab bhi naye version ke liye kisi existing function me logic change karna ho, to **existing function ko modify mat karo!** Ek naya isolated version banao:

```dart
// ❌ WRONG — Existing function modify karna (breaks old versions)
Widget _buildText(layer, scale) {
    // Changed calculation — old frames might break!
    finalY = layer['y'] * scale - newOffset;  
}

// ✅ CORRECT — New version-specific function (old function untouched)
Widget _buildTextV5(layer, scale) {
    // V5-specific calculation — only V5 frames use this
    finalY = layer['y'] * scale - newOffsetV5;
}

// Router function decides which to call:
Widget _buildText(layer, scale) {
    if (renderVersion >= 5) return _buildTextV5(layer, scale);
    return _buildTextV4(layer, scale);  // Original V4 logic, frozen
}
```

#### 🟡 Layer 2: Golden Snapshot Regression Test (Automated Catch-All)
Jab bhi developer `editor_canvas_widget.dart` ya `template_builder.js` me **koi bhi code change** kare (chahe ek line bhi), to deploy/merge se pehle system **automatically sabhi Golden Snapshot wale benchmark frames** ko naye code se re-compute karega aur golden baseline se compare karega.

Agar **kisi bhi frame me kisi bhi layer ka koi bhi value shift hua**, to system immediately alert dega:
```
🚨 REGRESSION DETECTED!
Your code change in _buildText() affected 3 frames:
  • Frame #123 (V4): text_title.finalY shifted by -8px
  • Frame #456 (V3): icon_phone.finalW shifted by +12px  
  • Frame #789 (V4): shape_bg.finalH shifted by +4px
  
Please fix before deploying!
```

Yeh test **har code change par automatically chalega** — developer ko manually check karne ki zaroorat nahi, system khud pakad lega ki kahin regression to nahi hua!

#### 🔴 Layer 3: Benchmark Control Frames (Manual Visual Verification)
Codebase me **5–10 "Control Frames"** permanently maintain honge jo har major rendering feature ka test case cover karengi:

| # | Control Frame Name | What It Tests |
|:---|:---|:---|
| 1 | `benchmark_point_text` | Single-line Point Text alignment, FittedBox scaling |
| 2 | `benchmark_paragraph_text` | Multi-line Paragraph Text wrapping, lineHeight |
| 3 | `benchmark_psd_mask` | PSD Clipping Mask auto-detection, ImageShader |
| 4 | `benchmark_vector_shapes` | V4 vector shapes: rect with rx/ry, gradient fill |
| 5 | `benchmark_icon_tint` | Icon rendering with tint_color |
| 6 | `benchmark_mixed_layers` | Complex frame with text + image + shape + icon |
| 7 | `benchmark_frame_slots` | Frame slot injection, frame background overlay |

Har major code change ke baad developer in control frames ko Web Editor aur Native Editor dono me visually verify karega.

---

## 📌 Web Editor Server-Side Re-Compute Options (For Dual Engine Validation)

Web Editor browser me (Fabric.js + Canvas) chalta hai, lekin Version Dashboard server par hai. Server-side Web re-computation ke liye 3 approaches:

### Option A: Pure Math Re-Compute (⭐ Recommended — Lightweight & Fast)
* Web Editor ke export formulas (`w = width × scaleX`, `x = aCoords.tl.x`, `fontSize = size × scaleY`) sab **mathematical formulas** hain
* Inhi formulas ko ek **PHP function** (`WebRenderSimulator::computeV5()`) me replicate kar sakte hain
* Input: Raw JSON values + target version → Output: Computed canvas coordinates
* Migration engine is function ko old version aur new version ke formulas se run karega aur compare karega
* **Speed:** Instant (~1ms per frame). **Accuracy:** 99%+ for coordinate/size calculations

### Option B: Headless Browser Render (Heavy but Pixel-Perfect)
* Server par **Puppeteer (Headless Chrome)** se actual Fabric.js canvas render karwa sakte hain
* V5 ke actual Web Editor code se frame render karega aur screenshot lega
* Golden web thumbnail (`web_thumbnail.webp`) se pixel-by-pixel compare hoga
* **Speed:** ~2-3 seconds per frame. **Accuracy:** 100% pixel-perfect

### Option C: Hybrid Approach (Best Balance)
* **Simple properties** (`x, y, w, h, fontSize, color`) → Pure Math Re-Compute (instant)
* **Visual output** (overall look) → Thumbnail pixel comparison (stored webp vs new render)

---

## 🛠️ Updated Next Steps Checklist (Complete Action Plan)

- [ ] **Step 1: Direct API JSON Serving (`HomeApi.php`)**
  - `HomeApi.php` ko modify karke `schema_json` aur `render_version` directly API response me expose karna without requiring ZIP download.
- [ ] **Step 2: Mobile App Hybrid Renderer & Asset Caching (`editor_canvas_widget.dart`)**
  - ZIP extraction logic ko bypass karke direct `schema_json` parse karna.
  - Image layers (`type: 'image'`) ke liye `CachedNetworkImage` + Disk Cache Manager integrate karna.
  - `_checkAssetsReady()` safety check add karna for `3.0x` Ultra HD Export (`boundary.toImage`).
- [ ] **Step 3: High-Scale Optimizations (Redis & `304 Not Modified`)**
  - Laravel me Redis cache layer connect karna (`template_json_{UUID}`).
  - `last_updated` conditional sync (`ETag` / `HTTP 304`) API header management in Flutter.
- [ ] **Step 4: Web Editor Diff Review Modal (`template_builder.js`)**
  - One-Click Version Upgrade Diff calculation engine.
  - Side-by-Side preview toggle modal UI before `saveFrame`.
- [ ] **Step 5: Golden Snapshot Baseline System**
  - `golden_renders` DB table create karna (frame_id, version, native_computed, web_computed, snapshots).
  - Web Editor publish flow me web computed values + thumbnail auto-capture karna.
  - Native Editor first render me native computed values capture karna.
- [ ] **Step 6: Version Dashboard Dual Engine Validation**
  - `PosterMakerController@bulk_migrate` me Dual Engine (Web + Native) re-compute logic add karna.
  - Server-side `WebRenderSimulator` aur `NativeRenderSimulator` PHP classes banana jo version-specific formulas run karein.
  - Golden baseline se comparison logic (MATCH / MINOR_DRIFT / MISMATCH categorization).
- [ ] **Step 7: Structured Mismatch Review Popup**
  - `version_control.blade.php` me responsive mismatch popup modal UI banana.
  - Frame name, Layer name, Property name, Old Value, New Value, Diff — structured table format.
  - Action buttons: Approve & Commit, Auto-Compensate, Open in Editor.
- [ ] **Step 8: Auto-Compensate Back-Calculation Engine**
  - Simple linear properties (`x, y, w, h`) ke liye automatic value adjustment.
  - Complex properties ke liye "Manual Review Required" flag.
  - Cascade dependency detection (fontSize ↔ y offset).
- [ ] **Step 9: Regression Bug Protection**
  - Immutable version-isolated helper functions architecture enforce karna.
  - Golden Snapshot automated regression test setup karna (runs on every code change).
  - 5-10 Benchmark Control Frames create aur maintain karna.

---

## 📊 Complete Data Flow Diagram (End-to-End):

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        WEB EDITOR (template_builder.js)                     │
│                                                                             │
│  User designs frame → Clicks "Publish"                                      │
│  │                                                                          │
│  ├── exportArteraSchema() → Bakes w/h, normalizes x/y, vectorizes shapes   │
│  ├── exportLegacyJson() → Legacy format for backward compat                │
│  ├── canvas.toDataURL() → Web Thumbnail (Golden Web Snapshot)              │
│  └── POST /admin/template-builder/saveFrame                                │
│       Body: { schema_json, legacy_json, thumbnail }                        │
└─────────────────────────────┬───────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                     LARAVEL BACKEND (TemplateBuilderController.php)          │
│                                                                             │
│  1. Save schema_json + legacy_json → MySQL (editor_templates table)        │
│  2. Save Base64 images → /uploads/editor/templates/{UUID}/assets/          │
│  3. Generate ZIP → /uploads/custom_frames_zips/{name}.zip                  │
│  4. Save render_version in DB                                              │
│  5. Store Golden Baseline → golden_renders table                           │
│     (web_computed + native_computed + snapshots)                            │
│  6. Push to Redis Cache (template_json_{UUID})                             │
│  7. Upload to DigitalOcean Spaces CDN (if configured)                      │
│  8. Save revision backup → template_revisions table                        │
└───────────────┬─────────────────────────────────┬───────────────────────────┘
                │                                 │
                ▼                                 ▼
┌───────────────────────────────┐  ┌──────────────────────────────────────────┐
│  VERSION CONTROL DASHBOARD     │  │  MOBILE APP (Flutter Native Editor)      │
│  (/admin/Frame/version-control)│  │                                          │
│                                │  │  GET /api/template/{uuid}                │
│  Admin selects frames →        │  │  ├── Redis Cache Hit → 5ms JSON response │
│  "Apply Migration" V4→V5       │  │  ├── ETag/304 Smart Sync                 │
│  │                             │  │  └── Parse schema_json / legacy_json     │
│  ├── Dual Engine Validation    │  │                                          │
│  │   ├── Web Re-Compute (V5)   │  │  Render Pipeline:                        │
│  │   ├── Native Re-Compute(V5) │  │  ├── Text: _buildText() with version     │
│  │   └── Compare vs Golden     │  │  │   isolation (V4 frozen, V5 new)        │
│  │                             │  │  ├── Image: CachedNetworkImage from CDN  │
│  ├── ✅ Match → Auto-commit    │  │  ├── Shape: _buildVectorShape() (V4+)    │
│  ├── ⚠️ Drift → Commit+Log    │  │  ├── Icon: _buildIconLayer() (V4+)       │
│  └── ❌ Mismatch → Popup      │  │  └── InteractiveLayer (Positioned)       │
│      ├── Approve & Commit      │  │                                          │
│      ├── Auto-Compensate       │  │  Export: boundary.toImage(pixelRatio:3.0)│
│      └── Open in Editor        │  │  (Pre-check: _checkAssetsReady())        │
└───────────────────────────────┘  └──────────────────────────────────────────┘
```

---
*Note: Any modifications to LOCKED sections (e.g. `render_version`, Web Editor rendering, PSD clipping mask, Native Editor image sizing) will strictly follow the Workspace Rules (`AGENTS.md`) and ask for passwords (`Brijesh@1415`) before code changes.*

---
---

# 📦 Appendix A–F: Detailed Technical Breakdowns (From Conversation)

**Added:** 14 July 2026  
**Context:** Yeh sections conversation me discuss kiye gaye important technical details hain jo upar ke phases me cover nahi hue the.

---

## 📦 Appendix A: Hybrid Data Split — Exact Layer Type Breakdown (Database vs Local Cache)

Hybrid Architecture me har layer type ka data kahan se aayega — yeh exact split hai:

### 🟢 Database (API Response — JSON Payload) se kya aayega:
Jab Mobile App API request karega (`GET /api/template/{uuid}`), Server **mili-seconds me lightweight JSON string (`schema_json`)** return karega jisme yeh sab hoga:

| Layer Type | Kya Data Aayega (JSON Properties) | File Download Zaroorat? |
|:---|:---|:---|
| **Text** (`type: 'text'`) | Pure text value (`"Happy Diwali"`), `name`, `x, y, w, h`, `fontFamily`, `fontSize`, `color/font_color` (Hex/Gradient), `justification`, `textKind` (`point` / `paragraph`), `_is_single_line` flag | ❌ **No file download** — Flutter `Text` widget se direct RAM me render |
| **Vector Shape** (`type: 'shape'`, `render_version >= 4`) | Shape type (`rect`, `circle`, `polygon`), `x, y, w, h`, exact `fill` (Hex ya Linear Gradient), `stroke`, `strokeWidth`, border radius (`rx, ry`), polygon `points` array | ❌ **No file download** — Flutter `Container` / `CustomPaint` se 100% vector draw |
| **Icon** (`type: 'icon'`, `render_version >= 4`) | Icon metadata (`iconName: 'mdi:phone'`, `iconProvider`, `color`, `x, y, w, h`) | ❌ **No file download** — Flutter icon pack ya vector path se direct draw |
| **Image / Sticker** (`type: 'image'`) | Image metadata (`name`, `x, y, w, h`, `mask_layer_id`, `z_index`) + **CDN Asset URL** (`"src": "https://cdn.artera.in/uploads/editor/templates/UUID/assets/sticker_1.png"`) | ✅ **Download + Cache** via `CachedNetworkImage` |

### 🔵 Local Disk Cache (`CachedNetworkImage` / Phone Memory) se kya aayega:
* **SIRF Physical Raster Image Files** (Stickers, Frame Background PNGs, User Logos/Photos)!
* Text, Vector Shapes, aur Icons → JSON parse hote hi mili-seconds me draw ho jayenge (koi download nahi)
* Images → `CachedNetworkImage` se:
  * **First Time (Cache Miss)**: URL se background download → phone ke `/cache/` folder me save
  * **Next Time (Cache Hit)**: Bina internet ke, phone ki disk se **0ms** instant load

---

## 📦 Appendix B: Web Editor Save Pipeline — Fixed Calculation (Native Compatibility Baking)

Jab user Web Editor me **Publish / Save** click karta hai, to `template_builder.js` ke `exportArteraSchema()` function me saving se PEHLE ek **Fixed Native-Compatible Calculation** apply hoti hai:

### Exact Calculations jo Save ke waqt hoti hain:

| Property | Raw Fabric.js Value | Baking Formula (Save ke waqt apply hota hai) | JSON me Saved Value |
|:---|:---|:---|:---|
| **Width (`w`)** | `obj.width = 200`, `obj.scaleX = 2.5` | `w = Math.round(obj.width × obj.scaleX)` | `w: 500` |
| **Height (`h`)** | `obj.height = 100`, `obj.scaleY = 1.5` | `h = Math.round(obj.height × obj.scaleY)` | `h: 150` |
| **X Position** | Fabric center-origin coordinates | `x = obj.aCoords.tl.x` (Absolute Top-Left after `setCoords()`) | `x: 240` |
| **Y Position** | Fabric center-origin coordinates | `y = obj.aCoords.tl.y` (Absolute Top-Left) | `y: 380` |
| **Font Size** | `obj.fontSize = 24`, `obj.scaleY = 1.5` | `size = Math.round(obj.fontSize × Math.abs(obj.scaleY))` | `size: 36` |
| **Scale** | `obj.scaleX = 2.5`, `obj.scaleY = 1.5` | Reset to `scaleX: 1, scaleY: 1` (dimensions already baked) | `scaleX: 1, scaleY: 1` |

**Result:** JSON me save hone wala data hamesha **pre-baked, clean, aur base `1080×1080` canvas resolution par standardized** hota hai. Native Editor ko koi complicated Fabric.js hack ya scale interpretation nahi karna padta!

### Important Point:
**Yeh Web Editor ka save calculation ek FIXED JavaScript code hai.** Agar aap sirf Native Editor (`editor_canvas_widget.dart`) me koi change karte hain, to Web Editor ka yeh logic **apne aap change NAHI hoga!** Agar Native Editor me koi aisa badlav ho jiske liye Web Editor se aane wali value ka calculation badalna zaroori ho, to aapko **dono files me manually code update** karna padega + `CURRENT_RENDER_VERSION` increment karna hoga.

---

## 📦 Appendix C: Native Editor Receive Pipeline — Screen Ratio Scaling

Jab clean baked JSON Database se Native Editor (`editor_canvas_widget.dart`) me aata hai, to Native Editor bas **ek simple physical scaling calculation** karta hai:

### Scale Factor Calculation:
Template base resolution `1080px` hai, lekin phone ki physical screen par editor widget man lijiye `360px` wide hai:
$$\text{scale} = \frac{\text{Phone Widget Width}}{\text{Template Base Width}} = \frac{360}{1080} = 0.333$$

### Layer Rendering:
Database se aaye hue values par seedha `scale` multiply hota hai:

| Property | DB JSON Value | Scale | Final Pixel Value |
|:---|:---|:---|:---|
| `x` | `240` | `× 0.333` | `finalX = 80px` |
| `y` | `380` | `× 0.333` | `finalY = 126.5px` |
| `w` | `500` | `× 0.333` | `finalW = 166.5px` |
| `h` | `150` | `× 0.333` | `finalH = 50px` |
| `fontSize` | `36` | `× (ppi/72) × scale` | `finalFontSize = 36 × 1.33 × 0.333 = 16px` |

**Result:** Native Editor ko koi complicated hack ya adjustment nahi lagani padti (`render_version >= 3/4` me). Bas `scale` multiply karo — pixel-perfect render! ✅

---

## 📦 Appendix D: Current Implementation Status (READY ✅ vs BAAKI HAI ⏳)

| # | Component / Feature | Current Status | Remarks |
|:---|:---|:---|:---|
| 1 | **Vector Shapes, Icons & Text Math (`render_version 4`)** | ✅ **READY (Live)** | Web (`template_builder.js`) aur Mobile (`editor_canvas_widget.dart`) dono me implement ho chuka hai |
| 2 | **Ultra HD Export (`3.0x Pixel Ratio`)** | ✅ **READY (Live)** | `native_editor_screen.dart` me `boundary.toImage(pixelRatio: 3.0)` live kaam kar raha hai |
| 3 | **Automatic Revisions Backup (`template_revisions`)** | ✅ **READY (Live)** | Backend publish par purane JSON ka backup bana raha hai |
| 4 | **Version Control Dashboard (Basic Bulk Migration)** | ✅ **READY (Live)** | `/admin/Frame/version-control` se multiple frames ka version change ho raha hai — lekin bina Dual Engine validation ke |
| 5 | **Direct API JSON Serving (No ZIP Extraction)** | ⏳ **ABHI BAAKI HAI** | Currently ZIP download & unzip chal raha hai |
| 6 | **Image Asset Caching via `CachedNetworkImage`** | ⏳ **ABHI BAAKI HAI** | Hybrid switch ke waqt add kiya jayega |
| 7 | **Redis Caching & `304 Not Modified` Sync** | ⏳ **ABHI BAAKI HAI** | High-Scale optimization ke waqt lagayenge |
| 8 | **Bundled Batch API (Single Request Payload)** | ⏳ **ABHI BAAKI HAI** | Festival Detail List ke liye |
| 9 | **Golden Snapshot Baseline System** | ⏳ **ABHI BAAKI HAI** | `golden_renders` table + dual snapshot capture |
| 10 | **Version Dashboard Dual Engine Validation** | ⏳ **ABHI BAAKI HAI** | Web + Native re-compute + golden comparison |
| 11 | **Structured Mismatch Review Popup** | ⏳ **ABHI BAAKI HAI** | Frame/Layer/Property level diff table |
| 12 | **Auto-Compensate Back-Calculation Engine** | ⏳ **ABHI BAAKI HAI** | Simple linear properties auto-fix |
| 13 | **One-Click Version Diff Review Modal (Web Editor)** | ⏳ **ABHI BAAKI HAI** | Side-by-side preview before saveFrame |
| 14 | **Regression Test Admin Page** | ⏳ **ABHI BAAKI HAI** | `/admin/regression-test-log` |
| 15 | **Benchmark Control Frames** | ⏳ **ABHI BAAKI HAI** | 5-10 permanent test reference frames |

---

## 📦 Appendix E: Code Size Management (Versioned Functions Growth & Cleanup)

### Concern:
Jab har naye `render_version` ke liye `_buildTextV5()`, `_buildTextV6()`, `_buildImageLayerV5()` jaise nayi functions banenge, to codebase ka size badhta jayega.

### Reality Check (Kitna Badhega):
* Ek `_buildText()` function lagbhag **80–100 lines** ki hoti hai
* Har version me max **200–300 lines** ka addition hoga poore `editor_canvas_widget.dart` me
* `editor_canvas_widget.dart` abhi **~2395 lines** hai
* **5 versions baad bhi total file ~3500–4000 lines rahegi** — yeh Flutter ke liye bilkul normal aur manageable hai!

### Cleanup Solution (Dead Version Removal):
Jab purane version ke **saare frames migrate ho chuke hon** (Version Control Dashboard me check karo), tab purane version ki function safely delete ki ja sakti hai:

```
Version Control Dashboard me check karo:
  V1 frames remaining: 0   ← Sab migrate ho chuke! → _buildTextV1() DELETE ✅
  V2 frames remaining: 0   ← Sab migrate ho chuke! → _buildTextV2() DELETE ✅
  V4 frames remaining: 150 ← Abhi use me hain!     → _buildTextV4() KEEP 🔒
  V5 frames remaining: 80  ← New frames!            → _buildTextV5() KEEP 🔒
```

**Result:** Code kabhi bhi uncontrolled grow nahi hoga — sirf active versions ke functions rahenge, dead versions cleanup ho jayenge!

---

## 📦 Appendix F: Admin UI Locations — Test Results & Migration Results Kaha Dikhenge

### 🟢 1. Regression Test Results (Code Change ke baad):
**Admin Sidebar me naya section add hoga:**

```
Admin Sidebar:
├── Frame
│   ├── Frame Category
│   ├── Frame
│   └── Version Control              ← (Already exists — /admin/Frame/version-control)
├── 🧪 Regression Tests               ← (NEW Section)
│   ├── Test Logs                     ← /admin/regression-test-log
│   └── Benchmark Frames             ← /admin/benchmark-frames
```

**Test Log Page (`/admin/regression-test-log`)** me dikhega:
* Latest test run ka timestamp aur trigger (Code Deploy / Manual)
* Har benchmark frame ka PASS ✅ / FAIL ❌ status
* Failed frames me: Layer name, Property name, Golden Value, Current Value, Diff amount
* Past test runs ki history log

### 🔵 2. Bulk Version Migration Results (Version Dashboard se):
**Same existing page `/admin/Frame/version-control` par modal popup aayega:**

* Button click → *"Migrating... (3/5 frames processed)"* progress bar dikhega
* Process complete → **Modal Popup** overlay open hoga with:
  * Auto-committed frames ki list (✅ Match, no issues)
  * Mismatched frames ki detailed table (Frame name, Layer name, Property, Old Value, New Value, Diff)
  * Action buttons per mismatched frame: `[✅ Approve & Commit]` `[🔄 Auto-Compensate]` `[✏️ Open in Editor]`
* Page reload nahi hoga — modal se hi sab manage ho jayega

### Summary Table:

| Process | Trigger | Result Location (Admin URL) |
|:---|:---|:---|
| **Regression Test** | Code deploy / push to server | `/admin/regression-test-log` (New admin page) |
| **Benchmark Frames Management** | Manual setup | `/admin/benchmark-frames` (New admin page) |
| **Bulk Version Migration** | "Apply Migration" button on Version Dashboard | `/admin/Frame/version-control` (Modal popup on same page) |

---
*Note: Any modifications to LOCKED sections (e.g. `render_version`, Web Editor rendering, PSD clipping mask, Native Editor image sizing) will strictly follow the Workspace Rules (`AGENTS.md`) and ask for passwords (`Brijesh@1415`) before code changes.*
