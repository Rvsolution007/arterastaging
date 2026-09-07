<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdLiveIdentityMutationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/** Signed, server-only mutations for normal Pixel customer identities. */
class AdLiveIdentityController extends Controller
{
    public function create(Request $request, AdLiveIdentityMutationService $mutations)
    {
        return $this->handle($request, function (array $data) use ($mutations) {
            return response()->json(['identity' => $mutations->create($data)], Response::HTTP_CREATED);
        }, [
            'request_id' => ['required', 'uuid'],
            'occurred_at' => $this->occurredAtRules(),
            'source' => ['required', 'in:adlive,artera_pixel'],
            'identity' => ['required', 'array:name,email,phone'],
            'identity.name' => ['required', 'string', 'max:255'],
            'identity.email' => ['required', 'email', 'max:255'],
            'identity.phone' => ['nullable', 'string', 'regex:/^(?:|[0-9]{7,20})$/'],
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
        ], ['request_id', 'occurred_at', 'source', 'identity', 'password'], ['identity' => ['name', 'email', 'phone']]);
    }

    public function update(Request $request, AdLiveIdentityMutationService $mutations)
    {
        return $this->handle($request, function (array $data) use ($mutations) {
            return response()->json(['identity' => $mutations->update($data)]);
        }, [
            'request_id' => ['required', 'uuid'],
            'occurred_at' => $this->occurredAtRules(),
            'source' => ['required', 'in:adlive,artera_pixel'],
            'expected_updated_at' => ['nullable', 'date_format:Y-m-d\\TH:i:sP'],
            'identity' => ['required', 'array:artera_user_id,name,email,phone'],
            'identity.artera_user_id' => ['required', 'integer', 'min:1'],
            'identity.name' => ['sometimes', 'required', 'string', 'max:255'],
            'identity.email' => ['sometimes', 'required', 'email', 'max:255'],
            'identity.phone' => ['sometimes', 'nullable', 'string', 'regex:/^(?:|[0-9]{7,20})$/'],
        ], ['request_id', 'occurred_at', 'source', 'expected_updated_at', 'identity'], ['identity' => ['artera_user_id', 'name', 'email', 'phone']], true);
    }

    public function delete(Request $request, AdLiveIdentityMutationService $mutations)
    {
        return $this->handle($request, function (array $data) use ($mutations) {
            return response()->json(['identity' => $mutations->deactivate($data)]);
        }, [
            'request_id' => ['required', 'uuid'],
            'occurred_at' => $this->occurredAtRules(),
            'source' => ['required', 'in:adlive'],
            'artera_user_id' => ['required', 'integer', 'min:1'],
            // This value is accepted only after AdLive's own staff/admin
            // authorization; no role or permission can be supplied to Pixel.
            'admin_authorized' => ['required', 'accepted'],
        ], ['request_id', 'occurred_at', 'source', 'artera_user_id', 'admin_authorized']);
    }

    public function changeCredentials(Request $request, AdLiveIdentityMutationService $mutations)
    {
        return $this->handle($request, function (array $data) use ($mutations) {
            return response()->json(['identity' => $mutations->changePassword($data)]);
        }, [
            'request_id' => ['required', 'uuid'],
            'occurred_at' => $this->occurredAtRules(),
            'source' => ['required', 'in:adlive'],
            'artera_user_id' => ['required', 'integer', 'min:1'],
            'current_password' => ['required', 'string', 'max:1024'],
            'new_password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
        ], ['request_id', 'occurred_at', 'source', 'artera_user_id', 'current_password', 'new_password']);
    }

    public function adminResetCredentials(Request $request, AdLiveIdentityMutationService $mutations)
    {
        return $this->handle($request, function (array $data) use ($mutations) {
            return response()->json(['identity' => $mutations->changePassword($data, true)]);
        }, [
            'request_id' => ['required', 'uuid'],
            'occurred_at' => $this->occurredAtRules(),
            'source' => ['required', 'in:adlive'],
            'artera_user_id' => ['required', 'integer', 'min:1'],
            'admin_authorized' => ['required', 'accepted'],
            'new_password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
        ], ['request_id', 'occurred_at', 'source', 'artera_user_id', 'admin_authorized', 'new_password']);
    }

    /**
     * Strictly decode the signed JSON bytes. Password-bearing failures never
     * log an exception because framework exception context can include input.
     *
     * @param array<string, mixed> $rules
     * @param array<int, string> $allowedRoot
     * @param array<string, array<int, string>> $allowedObjects
     */
    private function handle(Request $request, callable $action, array $rules, array $allowedRoot, array $allowedObjects = [], bool $requiresOneUpdate = false)
    {
        if ($request->attributes->get('adlive_profile_authenticated') !== true) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $json = json_decode($request->getContent(), false, 32, JSON_THROW_ON_ERROR);
            $data = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
            if (! $json instanceof \stdClass || array_diff(array_keys($data), $allowedRoot)) {
                throw ValidationException::withMessages(['body' => ['Use only the documented JSON fields.']]);
            }
            foreach ($allowedObjects as $field => $allowed) {
                if (! isset($json->{$field}) || ! $json->{$field} instanceof \stdClass
                    || array_diff(array_keys($data[$field]), $allowed)) {
                    throw ValidationException::withMessages(['body' => ['Use only the documented JSON fields.']]);
                }
            }

            $data = Validator::make($data, $rules)->validate();
            $data['request_id'] = strtolower($data['request_id']);
            if ($requiresOneUpdate && count(array_diff(array_keys($data['identity']), ['artera_user_id'])) === 0) {
                throw ValidationException::withMessages(['identity' => ['Provide at least one identity field to update.']]);
            }

            return $action($data);
        } catch (\JsonException) {
            return response()->json(['message' => 'The body must be a valid JSON object.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ValidationException $exception) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $exception->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        } catch (\Throwable) {
            Log::error('AdLive identity mutation failed.');

            return response()->json(['message' => 'Identity synchronization is temporarily unavailable.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /** @return array<int, mixed> */
    private function occurredAtRules(): array
    {
        return ['required', 'string', 'date', 'regex:/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d{1,6})?(?:Z|[+-]\\d{2}:\\d{2})$/'];
    }
}
