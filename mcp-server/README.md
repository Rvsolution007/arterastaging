# Artera Admin Analytics MCP Server

This is a **read-only** MCP server for the Artera owner. It lets ChatGPT answer live business questions such as:

- “How many people visited the website today?”
- “How many first-time installs and package purchases did we have this week?”
- “What revenue did completed subscriptions generate?”
- “How many support tickets arrived, and which template was downloaded most?”
- “Show the plan, activity, and recent payments of a selected user.”

It is not a CRM, does not create or modify Artera records, does not touch the poster editor/rendering contracts, and does not call any OpenAI API. ChatGPT receives only the MCP endpoint; it never receives the Artera API prefix, the Laravel Sanctum token, GA4 credentials, database access, payment IDs, or raw support/activity content.

```text
ChatGPT (owner only)
        │ MCP over HTTPS
        ▼
Artera Admin Analytics MCP Server
   ├── scoped Laravel Sanctum token ──► Artera read-only analytics API
   └── GA4 service account ───────────► Google Analytics Data API
```

## Included tools

| MCP tool | Live source | What it returns |
| --- | --- | --- |
| `admin_overview` | Artera Laravel | Registrations, installs, package sales, support, and labelled ad estimate. |
| `website_visitors` | Google Analytics 4 | Active/new users, sessions, page views, engagement rate. |
| `install_summary` | Artera Laravel | Total installs, unique first installs, and uninstall events. |
| `package_sales_summary` | Artera Laravel | Completed purchases, revenue INR, paying users, plan breakdown. |
| `ad_revenue_summary` | Artera Laravel | Ad events plus an **estimate**, never falsely labelled actual AdMob revenue. |
| `support_ticket_summary` | Artera Laravel | Ticket counts by status and category; no ticket body. |
| `top_templates` | Artera Laravel | Most-downloaded festival, category, or custom templates. |
| `review_summary` | Artera Laravel | Synced Play Store review count, average, positive count, rating distribution. |
| `search_users` | Artera Laravel | Paginated owner search; email/mobile are masked. |
| `user_details` | Artera Laravel | User plan, business summary, recent payment summary, tickets, usage. |
| `user_activity` | Artera Laravel | Paginated activity names and timestamps only; no IP, user-agent, or raw payload. |

All tools are read-only. The MCP server registers no write action.

## Laravel security contract

The repository adds a small API contract solely for this MCP service:

- `App\Http\Middleware\EnsureMcpAnalyticsAccess` requires a Sanctum token with only `mcp:analytics` ability.
- The account e-mail must be in `MCP_ANALYTICS_ADMIN_EMAILS`; it defaults to `arterapixel7@gmail.com`.
- The token is issued with `php artisan artera:mcp-issue-token`; the command never changes the normal mobile-app token.
- The analytics controller has `GET` routes only and returns aggregates/minimised user data. It excludes passwords, bearer tokens, payment IDs, IP addresses, device hashes, user agents, raw activity payloads, ticket messages, and ticket descriptions.
- Date ranges are capped at 366 days; user searches/page sizes are bounded; all requests are rate-limited.

The Laravel API uses the project’s configured API prefix, rather than assuming a public `/api` path. The MCP service therefore uses `ARTERA_API_BASE_URL` containing the full private API-prefix URL. Never commit or paste that value into chat.

### Laravel deployment

Set these production environment variables on the Laravel server:

```dotenv
MCP_ANALYTICS_ADMIN_EMAILS=arterapixel7@gmail.com
MCP_ANALYTICS_TOKEN_NAME=mcp-analytics
MCP_ANALYTICS_MAX_DATE_RANGE_DAYS=366
MCP_ANALYTICS_MAX_PAGE_SIZE=50
```

Deploy the Laravel changes, then refresh generated application metadata:

```powershell
php artisan config:clear
php artisan route:clear
php artisan route:list | Select-String -Pattern "admin/mcp"
php artisan artera:mcp-issue-token arterapixel7@gmail.com --expires-days=30
```

Copy the one-time token output directly into the MCP server’s `ARTERA_API_TOKEN` environment variable. Do not save it in source control, a screenshot, or ChatGPT. Reissue it before it expires:

```powershell
php artisan artera:mcp-issue-token arterapixel7@gmail.com --expires-days=30 --replace
```

The current Artera mobile login rotates its normal `mobile-app` token and does not offer a refresh-token endpoint. The dedicated MCP token intentionally avoids that login flow and has an explicit maximum 90-day lifetime. This is safer than storing your password in the MCP server.

## GA4 website visitor setup

The supplied screenshots confirm the Artera Pixel GA4 property ID is `455458344`, the stream is active, and the measurement ID is installed. A measurement ID alone can send analytics events, but it cannot read reports. The MCP server needs a separate read-only service account.

1. In a Google Cloud project controlled by Artera, enable **Google Analytics Data API**.
2. Create a service account and JSON key with the least necessary cloud access.
3. In GA4 Property Access Management for property `455458344`, add the service-account e-mail as **Viewer**.
4. Store the JSON key in your deployment secret manager or mount it as a protected file, for example `/run/secrets/artera-ga4.json`.
5. Set `GOOGLE_APPLICATION_CREDENTIALS=/run/secrets/artera-ga4.json` in the MCP container/host. Do not commit this file.

Google’s official quickstart documents the service-account/API enablement/property-access steps, and the Data API expects the numeric GA4 property ID. [Google Analytics API quickstart](https://developers.google.com/analytics/devguides/config/admin/v1/quickstart), [GA4 property ID guidance](https://developers.google.com/analytics/devguides/reporting/data/v1/property-id)

## Ad revenue truthfulness

Artera currently records banner, interstitial, and rewarded ad events. The server uses the same eCPM estimate logic already used by the admin dashboard and labels the result `estimated_revenue_inr` with `actual_revenue_available: false`.

For actual paid AdMob revenue, add an AdMob Reporting API source/credentials in a separate follow-up. Until then, the MCP server will never present its estimate as accounting-grade revenue.

## MCP server setup

### Windows

```powershell
cd C:\xampp\htdocs\artera\mcp-server
Copy-Item .env.example .env
npm.cmd install
npm.cmd run build
npm.cmd start
```

### Linux

```bash
cd /path/to/artera/mcp-server
cp .env.example .env
npm install
npm run build
npm start
```

Fill only the real values in `.env`:

```dotenv
MCP_ACCESS_TOKEN=<32+ character random secret>
ARTERA_API_BASE_URL=https://arterapixel.com/<private-api-prefix>
ARTERA_API_TOKEN=<one-time Laravel command output>
GA4_PROPERTY_ID=455458344
```

The server health check is `GET /healthz`. It returns only `{ "status": "ok" }` and never tests or reveals upstream credentials.

## Docker

```bash
cd mcp-server
cp .env.example .env
# Fill .env and inject GA4 JSON through a secret mount/environment.
docker compose up --build -d
docker compose logs -f artera-admin-analytics-mcp
```

The Compose file binds port 3001 to loopback, uses a read-only container filesystem, and creates an in-memory `/tmp`. Put a TLS-terminating reverse proxy or secure MCP tunnel in front of it. Do not publish the port directly to the internet.

## ChatGPT connection: owner-only access

`MCP_ACCESS_TOKEN` protects controlled local/service clients. For a remote ChatGPT app, place an OAuth/OIDC identity-aware proxy (for example, your enterprise identity gateway) in front of the MCP service and allow only `arterapixel7@gmail.com`. That proxy must validate the identity before it forwards traffic; the MCP server should not trust browser-provided e-mail headers.

Then use the MCP service’s public HTTPS URL, e.g. `https://analytics-mcp.yourdomain.com/mcp`, in ChatGPT Developer Mode → Apps → Create. Complete the OAuth prompt, scan tools, and test in a new chat. Start with:

> “Give me today’s Artera admin overview. Separate actual package revenue from estimated ad revenue.”

Custom remote MCP availability is controlled by the ChatGPT account/workspace. Current OpenAI guidance documents full custom MCP/write support for Business and Enterprise/Edu, with plan/UI availability subject to change. This server itself needs no OpenAI API key and makes no OpenAI API request. [Developer mode and MCP apps in ChatGPT](https://help.openai.com/en/articles/12584461-developer-mode-and-mcp-apps-in-chatgpt)

## Verification

```powershell
# Laravel syntax and route contract
php -l app\Http\Controllers\Api\AdminMcpAnalyticsController.php
php -l app\Http\Middleware\EnsureMcpAnalyticsAccess.php
php artisan route:list | Select-String -Pattern "admin/mcp"

# MCP server
cd mcp-server
npm.cmd run typecheck
npm.cmd test
npm.cmd run build
```

Before production, test a newly issued token against every Laravel endpoint, verify the owner-only gateway policy, and test GA4 with a short date range. Never use real customer data in logs or test screenshots.

## Troubleshooting

| Symptom | Meaning / fix |
| --- | --- |
| `AUTHENTICATION_FAILED` | Reissue the dedicated `mcp:analytics` Laravel token; do not use a mobile-app token. |
| `UPSTREAM_NOT_FOUND` | `ARTERA_API_BASE_URL` is missing the configured private API prefix, or Laravel routes were not deployed/cleared. |
| `ANALYTICS_NOT_CONFIGURED` | Add GA4 service-account Viewer access and secret credentials; the measurement ID by itself is insufficient. |
| Ad revenue is shown as estimated | Expected until a separate AdMob Reporting API integration is approved and configured. |
| User details look incomplete | The server intentionally removes high-risk fields; it returns admin-relevant summaries only. |
| ChatGPT cannot scan the MCP server | Verify HTTPS/TLS, remote reachability, OAuth gateway configuration, and the account’s Developer Mode availability. |
