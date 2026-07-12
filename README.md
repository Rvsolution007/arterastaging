# Artera — AI-Powered Business Poster Maker

A Laravel-based backend + Flutter mobile app for creating marketing posters and social media content.

---

## ⚠️ Security Warning — Rotate Previously Hardcoded Secrets

> **If you cloned this repo before July 2026, the following secrets appeared in git history and MUST be rotated immediately:**

| Secret | Where it was | Action required |
|--------|-------------|-----------------|
| `arterapixel2026` | `key.properties` (keystore password) | Generate a new keystore and update Play Console |
| Gmail app password (MAIL_PASSWORD) | `.env` (local only, not committed) | Low risk — rotate if env was ever exposed |
| RunPod API key | `.env` (local only, not committed) | Rotate at https://runpod.io/console/user/settings |

> Git history cannot be "fixed" by deleting files — the secret is still in old commits. Rotating the secret is the only safe remediation.

---

## 🔒 Security Architecture

### Where Secrets Live

All secrets are stored in environment variables loaded from `.env` (never committed to git).

| Secret Type | Storage Location | Notes |
|-------------|-----------------|-------|
| DB credentials | `.env` → `DB_*` vars | Server-side only |
| Gmail app password | `.env` → `MAIL_PASSWORD` | Use App Password, not account password |
| Google Cloud / Vertex AI SA | DB (`ai_settings` table, AES-256 encrypted) | Uploaded via Admin UI |
| Firebase Service Account | DB (`notification_settings` table, AES-256 encrypted) | Uploaded via Admin UI |
| RunPod API Key | `.env` → `RUNPOD_API_KEY` | Server-side only |
| Stripe keys | DB (`payment_settings` table) | `stripe_secret_key` is server-side only |
| WhatsApp Evolution API key | DB (`whatsapp_settings` table) | Server-side only |
| Android signing keystore password | `key.properties` (gitignored) | Never committed |

### What Is Safe to Expose Publicly

- `GOOGLE_ANALYTICS_ID` — designed to be public
- Stripe **publishable** key — intentionally public

### What Must Never Be in Frontend / Client Code

- Stripe **secret** key
- Google Cloud service account private key
- Firebase service account private key
- Any database credentials
- `RUNPOD_API_KEY`

---

## 🚀 Setup

### 1. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Fill in all values in `.env`. See `.env.example` for the full list with descriptions.

### 2. Android Signing Setup

```bash
cp key.properties.example key.properties
# Edit key.properties with your real keystore password
# Place upload-keystore.jks in the project root (do NOT commit it)
```

### 3. Google Cloud / Vertex AI

1. Create a Service Account in Google Cloud Console with `Vertex AI User` role.
2. Download the JSON key file.
3. In the Artera Admin panel → AI Settings → upload the JSON.
4. The system encrypts it with AES-256-CBC and stores it in the database.

### 4. Firebase (FCM Push Notifications)

1. Download your Firebase service account JSON from the Firebase Console.
2. In Artera Admin → Notification Settings → upload the JSON.
3. Same AES-256 encryption applies.

---

## 📁 Gitignored Files

The following sensitive files are excluded from version control:

```
.env
.env.production
key.properties
upload-keystore.jks
upload_certificate.pem
*.jks / *.keystore / *.pem
storage/app/firebase-service-account.json
```

---

## 📋 Environment Variables Reference

See [`.env.example`](.env.example) for the full list with descriptions and placeholder values.

---

## License

Proprietary — Artera Pixel © 2024-2026. All rights reserved.
