<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeLocalAssetUrls
{
    /**
     * Rewrite legacy localhost upload links for requests made from a local-network device.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!app()->environment('local') || !method_exists($response, 'getContent') || !method_exists($response, 'setContent')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || !str_contains($content, 'localhost')) {
            return $response;
        }

        $assetBaseUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/Artera/uploads';
        $escapedAssetBaseUrl = str_replace('/', '\\/', $assetBaseUrl);
        $response->setContent(strtr($content, [
            'http://localhost/artera/uploads' => $assetBaseUrl,
            'https://localhost/artera/uploads' => $assetBaseUrl,
            'http://127.0.0.1/artera/uploads' => $assetBaseUrl,
            'https://127.0.0.1/artera/uploads' => $assetBaseUrl,
            'http:\\/\\/localhost\\/artera\\/uploads' => $escapedAssetBaseUrl,
            'https:\\/\\/localhost\\/artera\\/uploads' => $escapedAssetBaseUrl,
            'http:\\/\\/127.0.0.1\\/artera\\/uploads' => $escapedAssetBaseUrl,
            'https:\\/\\/127.0.0.1\\/artera\\/uploads' => $escapedAssetBaseUrl,
        ]));

        return $response;
    }
}
