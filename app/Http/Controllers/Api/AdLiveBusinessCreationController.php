<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdLiveBusinessCreator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdLiveBusinessCreationController extends Controller
{
    public function __invoke(Request $request, AdLiveBusinessCreator $creator)
    {
        if ($request->attributes->get('adlive_profile_authenticated') !== true) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            // Decode only the signed body. Query parameters and transformed
            // request bags are deliberately not part of this contract.
            $json = json_decode($request->getContent(), false, 64, JSON_THROW_ON_ERROR);
            $data = json_decode($request->getContent(), true, 64, JSON_THROW_ON_ERROR);
            if (! $json instanceof \stdClass
                || ! ($json->business ?? null) instanceof \stdClass
                || array_diff(array_keys($data), ['artera_user_id', 'business'])
                || array_diff(array_keys($data['business']), ['name', 'category_id', 'sub_category_ids', 'business_type', 'products', 'website', 'location'])) {
                throw ValidationException::withMessages(['body' => ['Use only the documented JSON business fields.']]);
            }

            foreach (['sub_category_ids', 'products'] as $field) {
                if (! is_array($json->business->{$field} ?? null)) {
                    throw ValidationException::withMessages(['business.'.$field => ['The field must be a JSON list.']]);
                }
            }

            $data = Validator::make($data, $this->rules())->validate();

            return response()->json(['profile' => $creator->create($data)]);
        } catch (\JsonException $exception) {
            return response()->json(['message' => 'The body must be a valid JSON object.'], 422);
        } catch (ValidationException $exception) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $exception->errors()], 422);
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        } catch (\Throwable $exception) {
            // Do not log exception objects: they may include signed body data.
            Log::error('AdLive business creation failed; its transaction was rolled back.');

            return response()->json(['message' => 'Business creation is temporarily unavailable.'], 503);
        }
    }

    private function rules(): array
    {
        return [
            'artera_user_id' => ['required', 'integer', 'min:1'],
            'business' => ['required', 'array:name,category_id,sub_category_ids,business_type,products,website,location'],
            'business.name' => ['required', 'string', 'max:255'],
            'business.category_id' => ['required', 'integer', 'min:1'],
            'business.sub_category_ids' => ['required', 'array', 'min:1', 'max:50'],
            'business.sub_category_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'business.business_type' => ['required', 'string', Rule::in(['product', 'service', 'product_and_service'])],
            'business.products' => ['required', 'array', 'max:100'],
            'business.products.*' => ['required', 'string', 'distinct', 'max:255'],
            'business.website' => ['required', 'string', 'url', 'regex:/^https:\/\//i', 'max:2048'],
            'business.location' => ['required', 'string', 'max:1000'],
        ];
    }
}
