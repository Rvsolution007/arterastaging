<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdLiveBusinessProfileUpdater;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdLiveBusinessProfileUpdateController extends Controller
{
    public function __invoke(Request $request, AdLiveBusinessProfileUpdater $updater)
    {
        if ($request->attributes->get('adlive_profile_authenticated') !== true) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            // Use only the signed JSON bytes, never query parameters, files or
            // the globally trimmed/null-converted request parameter bag.
            $json = json_decode($request->getContent(), false, 64, JSON_THROW_ON_ERROR);
            $data = json_decode($request->getContent(), true, 64, JSON_THROW_ON_ERROR);
            if (! $json instanceof \stdClass
                || ! ($json->identity ?? null) instanceof \stdClass
                || ! ($json->business ?? null) instanceof \stdClass
                || array_diff(array_keys($data), ['request_id', 'occurred_at', 'source', 'identity', 'business'])) {
                throw ValidationException::withMessages(['body' => ['Use only the documented JSON profile fields.']]);
            }

            foreach (['sub_categories', 'business_types', 'products'] as $field) {
                if (property_exists($json->business, $field) && ! is_array($json->business->{$field})) {
                    throw ValidationException::withMessages(['business.'.$field => ['The field must be a JSON list.']]);
                }
            }

            $data = Validator::make($data, $this->rules())->validate();
            $data['request_id'] = strtolower($data['request_id']);

            return response()->json(['profile' => $updater->update($data)]);
        } catch (\JsonException $exception) {
            return response()->json(['message' => 'The body must be a valid JSON object.'], 422);
        } catch (ValidationException $exception) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $exception->errors()], 422);
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        } catch (\Throwable $exception) {
            // SQL exception messages/bindings and exception traces may contain
            // profile input. Log only a fixed operational event.
            Log::error('AdLive profile synchronization failed; its transaction was rolled back.');

            return response()->json(['message' => 'Profile synchronization is temporarily unavailable.'], 503);
        }
    }

    private function rules(): array
    {
        $rules = [
            'request_id' => ['required', 'uuid'],
            'occurred_at' => ['required', 'string', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/'],
            'source' => ['required', 'in:adlive'],
            'identity' => ['required', 'array:artera_user_id,name,email,phone'],
            'identity.artera_user_id' => ['required', 'integer', 'min:1'],
            'identity.name' => ['sometimes', 'required', 'string', 'max:255'],
            'identity.email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            // users.mobile_no is a legacy numeric column, so retain the
            // existing mobile-profile format rather than coercing a string.
            'identity.phone' => ['sometimes', 'string', 'regex:/^(?:|[0-9]{7,20})$/'],
            'business' => ['required', 'array:id,name,category,sub_categories,business_types,products,location,client_profile_version'],
            'business.id' => ['required', 'integer', 'min:1'],
            'business.name' => ['sometimes', 'required', 'string', 'max:255'],
            'business.category' => ['sometimes', 'required', 'array:id,name'],
            'business.category.id' => ['sometimes', 'required', 'integer', 'min:1'],
            'business.category.name' => ['sometimes', 'required', 'string', 'max:255'],
            'business.location' => ['sometimes', 'string', 'max:1000'],
            'business.client_profile_version' => ['sometimes', 'required', 'string', 'max:128'],
        ];
        foreach (['sub_categories' => 50, 'business_types' => 50, 'products' => 100] as $field => $max) {
            $rules['business.'.$field] = ['sometimes', 'array', 'max:'.$max];
            $rules['business.'.$field.'.*'] = ['required', 'array:id,name'];
            $rules['business.'.$field.'.*.id'] = ['required', 'integer', 'distinct', 'min:1'];
            $rules['business.'.$field.'.*.name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
