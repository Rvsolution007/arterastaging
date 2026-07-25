<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlayStoreReviewSyncService
{
    private const PUBLISHER_SCOPE = 'https://www.googleapis.com/auth/androidpublisher';

    /**
     * Fetches Google Play reviews through the official Android Publisher API
     * and keeps the local cache used by the Growth OS dashboard in sync.
     */
    public function sync(): int
    {
        $credentials = $this->credentials();
        $packageName = (string) config('services.google_play.package_name');
        if ($packageName === '') {
            throw new RuntimeException('GOOGLE_PLAY_PACKAGE_NAME is not configured.');
        }

        $accessToken = $this->accessToken($credentials);
        $pageToken = null;
        $synced = 0;

        do {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get("https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$packageName}/reviews", array_filter([
                    'maxResults' => 100,
                    'translationLanguage' => 'en',
                    'token' => $pageToken,
                ]))
                ->throw()
                ->json();

            foreach ($response['reviews'] ?? [] as $review) {
                $this->upsertReview($review);
                $synced++;
            }

            $pageToken = data_get($response, 'tokenPagination.nextPageToken');
        } while (!empty($pageToken));

        return $synced;
    }

    private function credentials(): array
    {
        $path = (string) config('services.google_play.service_account_json');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('GOOGLE_PLAY_SERVICE_ACCOUNT_JSON must point to a readable service-account JSON file.');
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('The Google Play service-account JSON is invalid.');
        }

        return $credentials;
    }

    private function accessToken(array $credentials): string
    {
        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64Url(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::PUBLISHER_SCOPE,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsignedToken = $header . '.' . $claims;

        if (!openssl_sign($unsignedToken, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign the Google Play access token request.');
        }

        $response = Http::asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsignedToken . '.' . $this->base64Url($signature),
            ])
            ->throw()
            ->json();

        if (empty($response['access_token'])) {
            throw new RuntimeException('Google Play did not return an access token.');
        }

        return $response['access_token'];
    }

    private function upsertReview(array $review): void
    {
        $comment = collect($review['comments'] ?? [])
            ->map(fn (array $item) => $item['userComment'] ?? null)
            ->filter()
            ->last();

        if (!is_array($comment) || empty($review['reviewId'])) {
            Log::warning('Skipping an invalid Google Play review response.');
            return;
        }

        $modifiedAt = $comment['lastModified'] ?? [];
        $seconds = (int) ($modifiedAt['seconds'] ?? time());

        $values = [
                'author_name' => $review['authorName'] ?? null,
                'star_rating' => (int) ($comment['starRating'] ?? 0),
                'review_text' => $comment['text'] ?? null,
                'review_date' => Carbon::createFromTimestampUTC($seconds),
                'updated_at' => now(),
            ];

        $query = \DB::table('play_store_reviews')->where('review_id', $review['reviewId']);
        if ($query->exists()) {
            $query->update($values);
            return;
        }

        \DB::table('play_store_reviews')->insert(array_merge($values, [
            'review_id' => $review['reviewId'],
            'created_at' => now(),
        ]));
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
