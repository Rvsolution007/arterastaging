<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AdLive internal SSO bridge
    |--------------------------------------------------------------------------
    |
    | This secret is used only by the AdLive server when it consumes an opaque
    | one-time ticket. It must be identical on both servers and never sent to
    | Flutter, a browser, logs, or a query string.
    |
    */
    'shared_secret' => env('ADLIVE_SSO_SHARED_SECRET'),

    'security_revoke_url' => env('ADLIVE_SSO_SECURITY_REVOKE_URL'),

    'request_timeout_seconds' => (int) env('ADLIVE_SSO_REQUEST_TIMEOUT_SECONDS', 8),

    // New bridge requests are signed with a timestamp and one-time nonce.
    // Keep signed-only mode disabled during the coordinated rollout so an
    // older AdLive deploy can consume an already-issued short-lived ticket.
    'require_signed_requests' => filter_var(env('ADLIVE_SSO_REQUIRE_SIGNED_REQUESTS', false), FILTER_VALIDATE_BOOL),
    'internal_request_max_age_seconds' => (int) env('ADLIVE_SSO_INTERNAL_REQUEST_MAX_AGE_SECONDS', 300),

    'authorization_code_ttl_seconds' => (int) env('ADLIVE_SSO_AUTHORIZATION_CODE_TTL_SECONDS', 90),

    // Temporary, fingerprint-only read endpoint for producing the legacy
    // client migration report. Keep false until the audit is approved.
    'migration_inventory_enabled' => filter_var(env('ADLIVE_SSO_MIGRATION_INVENTORY_ENABLED', false), FILTER_VALIDATE_BOOL),

    // Each browser redirect URI must be registered exactly. Comma separation
    // keeps local, staging and production values independent without putting
    // any secret in source control.
    'web_clients' => [
        'adlive-web' => [
            'redirect_uris' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADLIVE_WEB_REDIRECT_URIS', ''))))),
        ],
    ],

    // A ticket is intentionally short lived: it is exchanged immediately by
    // the mobile app after Artera has authenticated the user.
    'ticket_ttl_seconds' => (int) env('ADLIVE_SSO_TICKET_TTL_SECONDS', 60),
];
