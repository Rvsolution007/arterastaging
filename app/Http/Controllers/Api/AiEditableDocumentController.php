<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiEditableDocument;
use App\Models\FestivalAiGeneration;
use App\Models\StorageSetting;
use App\Models\User;
use App\Services\AiEditableDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Standalone API for the frame-free AI Editable V1 document.  No endpoint in
 * this controller reads or writes Frame JSON, ZIPs, render_version, or frame
 * layer flags.
 */
class AiEditableDocumentController extends Controller
{
    public function __construct(private AiEditableDocumentService $documents)
    {
    }

    public function show(Request $request, string $publicId)
    {
        $this->ensureEnabled();
        $document = $this->documentForUser($this->authenticatedUser($request), $publicId);

        return response()->json(['success' => true, 'document' => $this->payload($document)]);
    }

    public function create(Request $request)
    {
        $this->ensureEnabled();
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'manifest' => ['required', 'array'],
            'festival_ai_generation_id' => ['nullable', 'integer'],
        ]);

        $generation = null;
        if (!empty($validated['festival_ai_generation_id'])) {
            $generation = FestivalAiGeneration::query()
                ->whereKey($validated['festival_ai_generation_id'])
                ->where('user_id', $user->id)
                ->first();
            if (!$generation) {
                return $this->error('The source AI generation is unavailable.', Response::HTTP_NOT_FOUND);
            }
        }

        try {
            $document = $this->documents->create($user, $validated['manifest'], $generation);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['success' => true, 'document' => $this->payload($document)], Response::HTTP_CREATED);
    }

    public function save(Request $request, string $publicId)
    {
        $this->ensureEnabled();
        $document = $this->documentForUser($this->authenticatedUser($request), $publicId);
        $validated = $request->validate([
            'expected_revision' => ['required', 'integer', 'min:1'],
            'manifest' => ['required', 'array'],
        ]);

        try {
            $document = $this->documents->save(
                $document,
                (int) $validated['expected_revision'],
                $this->stripPresentationFields($validated['manifest'])
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $exception) {
            if ($exception->getCode() === 409) {
                return $this->error($exception->getMessage(), Response::HTTP_CONFLICT);
            }
            report($exception);

            return $this->error('The editable document could not be saved.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json(['success' => true, 'document' => $this->payload($document)]);
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) config('ai_editable_v1.enabled'), Response::HTTP_NOT_FOUND);
    }

    private function authenticatedUser(Request $request): User
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        $accessToken = $user ? $user->currentAccessToken() : null;

        if (!$user || !$accessToken || !$accessToken->can('mobile:access')) {
            abort(Response::HTTP_UNAUTHORIZED, 'Please sign in again to use AI Editable V1.');
        }

        if ($accessToken->expires_at && now()->greaterThanOrEqualTo($accessToken->expires_at)) {
            $accessToken->delete();
            abort(Response::HTTP_UNAUTHORIZED, 'Your session has expired. Please sign in again.');
        }

        if ($request->filled('userId') && (int) $request->input('userId') !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'The signed-in user does not match this request.');
        }

        return $user;
    }

    private function documentForUser(User $user, string $publicId): AiEditableDocument
    {
        abort_unless(Str::isUuid($publicId), Response::HTTP_NOT_FOUND);

        $document = AiEditableDocument::query()
            ->where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($document, Response::HTTP_NOT_FOUND);

        return $document;
    }

    private function payload(AiEditableDocument $document): array
    {
        return [
            'id' => $document->public_id,
            'status' => $document->status,
            'module_version' => $document->module_version,
            'document_contract' => $document->document_contract,
            'schema_version' => $document->schema_version,
            'revision' => $document->revision,
            'manifest' => $this->presentManifest((array) $document->manifest),
            'source_generation_id' => $document->festival_ai_generation_id,
            'updated_at' => optional($document->updated_at)->toIso8601String(),
        ];
    }

    private function error(string $message, int $status)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function presentManifest(array $manifest): array
    {
        $layers = (array) ($manifest['layers'] ?? []);
        foreach ($layers as $index => $layer) {
            if (!is_array($layer) || !is_array($layer['asset'] ?? null)) {
                continue;
            }
            $asset = $layer['asset'];
            $path = $asset['path'] ?? null;
            if (is_string($path) && $path !== '') {
                $asset['url'] = $this->assetUrl($path);
                $layer['asset'] = $asset;
                $layers[$index] = $layer;
            }
        }
        $manifest['layers'] = $layers;

        return $manifest;
    }

    /** Asset URLs are API presentation data, never user-editable document data. */
    private function stripPresentationFields(array $manifest): array
    {
        $layers = (array) ($manifest['layers'] ?? []);
        foreach ($layers as $index => $layer) {
            if (!is_array($layer) || !is_array($layer['asset'] ?? null)) {
                continue;
            }
            unset($layer['asset']['url']);
            $layers[$index] = $layer;
        }
        $manifest['layers'] = $layers;

        return $manifest;
    }

    private function assetUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            return Storage::disk('spaces')->url('uploads/' . ltrim($path, '/'));
        }

        $request = request();
        $basePath = str_replace('/index.php', '', $request->getBaseUrl());
        $origin = rtrim($request->getSchemeAndHttpHost() . $basePath, '/');

        return $origin . '/uploads/' . ltrim($path, '/');
    }
}
