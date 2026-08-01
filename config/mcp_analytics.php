<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP analytics administrators
    |--------------------------------------------------------------------------
    |
    | Only these existing Artera accounts may receive an mcp:analytics Sanctum
    | token. Keep this list in deployment environment variables, not code.
    |
    */
    'allowed_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', (string) env('MCP_ANALYTICS_ADMIN_EMAILS', 'arterapixel7@gmail.com')),
    ))),

    'token_name' => env('MCP_ANALYTICS_TOKEN_NAME', 'mcp-analytics'),
    'max_date_range_days' => (int) env('MCP_ANALYTICS_MAX_DATE_RANGE_DAYS', 366),
    'max_page_size' => (int) env('MCP_ANALYTICS_MAX_PAGE_SIZE', 50),
];
