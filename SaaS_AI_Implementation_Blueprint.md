# Master Implementation Plan: ArtEra Pixel x WhatsApp CRM SaaS Sync

Provide a brief description of the problem, background context, and what the change accomplishes. This document brings together Monetization Packages, AI PDF Catalog Recognition via Vertex AI, and Distributed Bi-Directional Webhook Sync between your Hostinger Shared Hosting (ArtEra Pixel) and VPS (WhatsApp CRM) servers.

## User Review Required
> [!IMPORTANT]
> The user is currently building up this plan progressively. Do not proceed to system coding until the user has submitted all their requirements and formally given approval.

---

## 1. System Architecture: Distributed Data Sync

### Choice: Event-Driven Webhooks (Decoupled Approach)
Based on hosting ArtEra Pixel on **Hostinger Shared Hosting** and WhatsApp CRM on **Hostinger VPS**, the Webhook event-architecture is implemented.

* **Hosting Cost Impact:** Zero required extra servers.
* **Mechanism Details:**
  1. **User Action:** User edits or AI generates product data from a PDF on ArtEra Pixel (Shared Hosting).
  2. **Webhook Trigger:** ArtEra Pixel securely fires an asynchronous HTTP `POST` Webhook to the VPS (`wa-crm.com/api/webhooks/ArtEra Pixel-sync`) with a JSON payload of the structured product data.
  3. **VPS Upsert:** The CRM VPS receives the payload, verifies a shared secret Hmac signature for security, and runs an `UPSERT` sequence (Update if ID exists, Insert if strictly new).
  4. **Reverse Flow:** If the CRM directly updates inventory after a user WhatsApp interaction, it strictly triggers a webhook back to ArtEra Pixel. 
  5. **Safety Gate:** Payloads contain a metaflag `origin="api_webhook"` so the receiving server knows not to re-trigger a bounce-back webhook, preventing infinite sync loops.

---

## 2. Artificial Intelligence: Dynamic PIM Data Generation

### GenAI Platform: Google Vertex AI (`gemini-1.5-flash`)
We utilize Google Vertex via secure JSON Service Accounts. The Flash model is explicitly chosen to keep generation costs mathematically negligible (₹0.006 per generation).

### The PDF Catalog Processing Engine
1. **Background Job Parsing:** When a user registers and uploads a PDF Business Brochure, Laravel background workers (Spati PDF-to-Image) unpacks the geometry into hi-res images.
2. **Visual AI Evaluation:** `gemini-1.5-flash` evaluates the images.
3. **Structured Finality:** A complex strictly typed system-prompt enforces JSON format.
```json
// Enforced JSON Validation Draft
{
  "products": [
     {"uid": "PRD-01", "name": "Unique Sofa", "is_combo": false, "desc": "Premium wood finish."},
     {"uid": "CMB-99", "name": "Sofa + Table Combo", "is_combo": true, "desc": "Festival offer pack."}
  ]
}
```
4. **Draft Phase:** Extracted items are cached in a temporary User Draft table until the Account owner clicks "Verify & Publish", at which point it commits and the Webhook fires to the VPS automatically.

---

## 3. Monetization & Dynamic Subscription Packages
The SaaS database logic will rely on a `packages` (or `subscription_plans`) table. Based on recent revisions, we have completely removed isolated, hardcoded fields like "Custom Post: Allow Edit", "Custom Post: Allow Choose Category", and "Magic Cloner Limit". 

Instead, the admin panel will feature a **Universal Feature Limit & Rewarded Video Limit System**. For *every* feature inside a package, the admin can set two things:
1. **Base Limit:** How many times the user can access this feature directly in their current plan.
2. **Rewarded Video Limit:** The absolute maximum number of times per month a user can watch a Rewarded Ad to get *extra* access to this feature. (e.g., if Admin sets it to 5, the user can watch up to 5 ads per month to get 5 extra uses of that feature).

### Universal Access Flow
* If the user tries to access a feature that belongs to an **upper package**, OR if they have **exceeded their current package's base limit**.
* The system checks the **Rewarded Video Limit** for that feature.
* If they haven't exhausted their ad limit, they get an option: "Watch Ad to Unlock". Once watched, the feature unlocks and the usage count increments. 

---

## 4. Hostinger Server Capacity Analysis (Non-Cloud Strategy)
Since all 100 product images per user will be stored **locally on Hostinger Shared Business Hosting** (without DigitalOcean Spaces) and no processing is done on the server (image overlays happen directly inside the Flutter app), here is the scientific capacity breakdown:

### A. Processing Load (CPU/RAM Impact)
* **The Reality:** When a user opens a Custom Post, the server only returns a lightweight JSON array and simple image URLs (e.g., `/uploads/product.jpg`). It does **not** process, merge, or resize images live using PHP GD/Imagick.
* **CPU Cost:** Almost **0%**. Your server is acting like a basic CDN just handing over files.
* **Concurrency:** Hostinger Business Shared hosting allows ~100 active entry processes. Since a JSON/Image fetch takes roughly ~50ms to 100ms, your server can easily handle **400 to 600 concurrent users** using the app at the exact same millisecond before hitting a 508 Resource Limit bottleneck.

### B. Storage Load (The 50GB Limit & Image Quality)
* **Image Compression Strategy (No Blurry Images!):** How can 150KB look high definition? The trick is format and dimensions. Instagram compresses 1080x1080 images to 80-150KB by default. 
* **The Method:** We will not use standard JPG. When a user uploads a product, the backend will convert it to **WebP format** and cap dimensions to exactly what's needed (e.g., if the final poster is 1080px, the product image inside it is never larger than 600x600px). A 600x600px WebP image is mathematically perfectly sharp at ~60-80KB.
* **Calculation:** 
  - 1 Ultra-sharp WebP Product Image = ~100KB.
  - 100 Products per User = **10MB per user**.
  - Total users before the 50GB NVMe is completely full: **~3,000 active catalog users**.
* *Strategy:* You are safe on Hostinger for up to 2,500 highly active users without sacrificing visual quality at all.

---

## 5. Product UX Strategy (The Digital Marketing Perspective)

You are 100% right. Bulk download is a "Suicide Feature." They will download 100 posts on Day 1, cancel their subscription, and never open the app again. Here is the 20-Year Digital Marketing solution to create an addictive daily loop that strictly separates Package 2 and Custom Posts:

### A. The "Daily Drip" / "Product of the Day" Engine (Business Post) -> *Package 2 (Silver)*
* **The Market Problem:** Small business owners always ask, *"Aaj WhatsApp status pe kya upload karu?"* (Consistency struggle).
* **The Solution (What it is):** Business Post operates as an **Automated Daily Content Engine**. It is absolutely un-editable. 
* **User Experience:** The user uploads their catalog once. Then, every single morning at 9:00 AM, your app sends a Push Notification: *"Good morning! Here is your 'Product of the Day' poster. Share it now!"* The user opens the app, and the system has randomly pulled *just ONE* product from their catalog, placed an AI hook on it (e.g., "Trending Today", or "Our Best Seller"), and formatted it beautifully. The user just clicks "Share to WA Status".
* **Why it sells (Retention):** They MUST open the app daily. They are paying for **"Marketing Automation."** They don't have to think about what to post today. Your app thinks for them.
* **Limitation vs Package 3:** They cannot choose which product appears today. They cannot change the generic text. It's just a quick, daily status update.

### B. The "Studio Campaigns" (Custom Post) -> *Moved to Package 2 (Silver)*
* **The Setup:** We move the "Custom Post" (Canva Studio editor) into Package 2 but limit it (e.g., 10 uses per month). 
* **Category Control:** A Silver user has the power to tell the AI exactly what *type* of post they want to generate today:
  1. Product Detail Post
  2. Promotional / Sale Post
  3. Client Review / Testimonial
  4. "Why Choose Us" / Branding Post
  5. Entertainment / Meme Post 
* **The UX:** They choose "Client Review", select a product, and the AI generates an editable layout specifically designed for reviews using that product's data.

---

## 6. The "AI Magic Cloner" (Package 3 / Gold Exclusive)
As a veteran system architect, trying to code a flat JPG backward into perfect editable layers is a $1M R&D problem. However, we can create the *exact same magical feeling* using **"Template Vibe Matching & Injection"**. This will be the ultimate selling point of Package 3.

### How the Magic Cloner Works:
1. **Upload Inspiration:** User uploads an image of a beautiful competitor's ad (e.g., a modern dark-red shoe promotion). 
2. **Vision AI Analysis:** The image is sent to Vertex AI `gemini-1.5-pro-vision`. We ask the AI to extract exactly three things:
   - *Primary & Secondary Hex Colors* (e.g., #8B0000, #FFFFFF).
   - *Layout Structure Code* (e.g., "minimalist_center" or "split_screen").
   - *Font Vibe* (e.g., "bold_sans").
3. **Template Database Matching:** Your Laravel system queries the existing `custom_post_frames` database to find 3 templates that match "minimalist_center".
4. **Dynamic Code Injection:** Instead of just showing the template, the system dynamically injects the extracted Hex Colors (`#8B0000`) deep into the matched JSON template's background shapes, and formats the font.
5. **The Magic Result:** The user sees a template from your system that has *Instantly color-graded and adapted itself* to look exactly like their uploaded inspiration image, populated with their own product! They can now edit it fully in the Studio!

---

## 7. Comprehensive Ad Strategy & Placement Blueprint
This section outlines the step-by-step strategy for integrating ads to maximize revenue while preserving the premium user experience.

### Phase 1: Ad Strategy Architecture
1. **Ad Caching (Pre-load):**
   * *Concept:* Pre-load Rewarded and Interstitial ads in the background (e.g., on the Splash screen or Home screen).
   * *Execution:* When the user triggers an ad event, the ad will play instantly without buffering.
   * *Goal:* Prevent user drop-off due to slow ad loading times.
2. **Waterfall / Bidding (Mediation):**
   * *Concept:* Do not rely solely on AdMob. Integrate a mediation platform (AppLovin MAX or ironSource).
   * *Execution:* Setup mediation so that AdMob, Meta Audience Network (Facebook Ads), and others bid in real-time for the ad space.
   * *Goal:* Increase eCPM (revenue per 1000 impressions) by 20-30% automatically.
3. **Ad Frequency Capping:**
   * *Concept:* Limit the number of intrusive ads shown to a single user.
   * *Execution:* **Interstitial Ads** get a strict cap of 10-15 minutes between ads (Max 4-5 full-screen ads per user per day). **Rewarded Ads** have a higher cap since they are user-initiated.
   * *Goal:* Reduce app uninstall (churn) rates by not spamming the user.

### Phase 2: UI Ad Placements (Trigger Points)
* **A. Banner Ads (Persistent, Low eCPM):**
  * *Where:* Sticky at the bottom on specific tabs: **Home, Custom, AI Trend, and More**.
  * *Crucial Rule:* NEVER place banner ads inside the Studio Editor screen. The editor needs 100% of the screen real estate.
* **B. Native Ads (Seamless & Non-Intrusive):**
  * *Where:* Inside the template list whenever a user opens any template category (Festival Posts, Category Posts, and Custom Posts).
* **C. Interstitial Ads (Full-Screen Transitions):**
  * *Where:* Set to trigger exactly when the user goes to **download an image or video**. Applies to all post types.
* **D. Rewarded Video Ads (High eCPM, User-Initiated):**
  * *Where:* Always shown for **Premium Templates**.
  * *Where:* Always shown when a user tries to access a feature from an upper package or when their current package limits are exceeded (governed by the Admin's "Rewarded Video Limit").

### Phase 3: Admin Panel Enhancements
**Dynamic Ad Network Settings**
Add options in the Laravel Admin Panel to configure multiple ad networks on the fly without an app update.
* **AdMob:** Enable/Disable, App ID, Ad Unit IDs for Banner, Interstitial, Rewarded, Native.
* **Meta Audience Network:** Enable/Disable, App ID, Placement IDs.
* **AppLovin MAX / ironSource:** Enable/Disable, SDK Keys.

---

## Proposed Changes

### [Core Server / API Area]
#### [NEW] Custom Webhook Routes
#### [NEW] Vertex AI Controller Integration

### Open Questions
> [!WARNING]
> Please review the above details. Awaiting further points from you to append to this Master Document.
