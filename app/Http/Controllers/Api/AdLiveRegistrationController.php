<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendVerificationEmailJob;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\EmailVerified;
use App\Models\User;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AdLiveRegistrationController extends Controller
{
    /**
     * Return the central Artera business taxonomy to the AdLive sign-up form.
     * This signed endpoint keeps the browser free of Artera credentials while
     * ensuring both products use the exact same category data.
     */
    public function options(Request $request, AdLiveInternalRequestVerifier $requestVerifier)
    {
        if (! $requestVerifier->verify($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $categories = BusinessCategory::query()
            ->where('status', 1)
            ->with(['subCategories' => fn ($query) => $query->where('status', 1)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (BusinessCategory $category) => [
                'id' => (string) $category->id,
                'name' => (string) $category->name,
                'sub_categories' => $category->subCategories
                    ->map(fn (BusinessSubCategory $subCategory) => [
                        'id' => (string) $subCategory->id,
                        'name' => (string) $subCategory->name,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return response()->json(['categories' => $categories]);
    }

    /**
     * Create the Artera identity and business from AdLive's own form. The
     * shared password is received only by the two servers over TLS; it is
     * never returned to AdLive, the browser, or any log.
     */
    public function register(
        Request $request,
        AdLiveInternalRequestVerifier $requestVerifier,
        AdLiveBusinessProfileService $businessProfiles,
    ) {
        if (! $requestVerifier->verify($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
            'mobile_no' => ['required', 'digits_between:7,20', Rule::unique('users', 'mobile_no')->whereNull('deleted_at')],
            'business_name' => ['required', 'string', 'max:255'],
            'business_website' => ['nullable', 'url', 'max:2048'],
            'business_address' => ['nullable', 'string', 'max:1000'],
            'business_category_id' => ['required', 'integer'],
            'business_sub_category_ids' => ['nullable', 'array', 'max:20'],
            'business_sub_category_ids.*' => ['integer', 'distinct'],
            'logo' => ['nullable', 'array:mime,data'],
            'logo.mime' => ['required_with:logo', 'string', 'in:image/jpeg,image/png,image/webp'],
            'logo.data' => ['required_with:logo', 'string', 'max:2800000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();
        $data['email'] = Str::lower(trim($data['email']));
        $subCategoryIds = array_map('intval', $data['business_sub_category_ids'] ?? []);
        $logo = $this->decodeLogo($data['logo'] ?? null);

        $category = BusinessCategory::query()
            ->whereKey($data['business_category_id'])
            ->where('status', 1)
            ->first();

        if (! $category) {
            return $this->validationError('business_category_id', 'Choose an active business category.');
        }

        $validSubCategoryCount = count($subCategoryIds) === 0
            ? 0
            : BusinessSubCategory::query()
                ->where('business_category_id', $category->id)
                ->where('status', 1)
                ->whereIn('id', $subCategoryIds)
                ->count();

        if ($validSubCategoryCount !== count($subCategoryIds)) {
            return $this->validationError('business_sub_category_ids', 'Choose sub-categories that belong to the selected category.');
        }

        $logoPath = $this->persistLogo($logo);

        try {
            [$user, $business] = DB::transaction(function () use ($data, $subCategoryIds, $category, $logo) {
            $user = User::withTrashed()->where('email', $data['email'])->first();
            if ($user) {
                $user->restore();
                $user->name = $data['name'];
                $user->password = Hash::make($data['password']);
                $user->mobile_no = $data['mobile_no'];
                $user->status = 1;
                $user->login_type = 'normal';
                if (empty($user->referral_code)) {
                    $user->referral_code = strtoupper(Str::random(10));
                }
                $user->registration_source = 'adlive';
                $user->email_verified_at = $this->bypassesEmailVerification() ? now() : ($user->email_verified_at ?: null);
                $user->save();
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'mobile_no' => $data['mobile_no'],
                    'status' => 1,
                    'login_type' => 'normal',
                    'referral_code' => strtoupper(Str::random(10)),
                    'registration_source' => 'adlive',
                    'email_verified_at' => $this->bypassesEmailVerification() ? now() : null,
                ]);
            }

            $business = Business::where('user_id', $user->id)->first();
            if ($business) {
                $business->update([
                    'name' => $data['business_name'],
                    'email' => $data['email'],
                    'mobile_no' => $data['mobile_no'],
                    'website' => $data['business_website'] ?? '',
                    'address' => $data['business_address'] ?? '',
                    'logo' => $logo['file_name'] ?? $business->logo,
                    'business_category_id' => $category->id,
                    'business_sub_category_ids' => $subCategoryIds,
                    'status' => 1,
                    'is_default' => 1,
                ]);
            } else {
                $business = Business::create([
                    'user_id' => $user->id,
                    'name' => $data['business_name'],
                    'email' => $data['email'],
                    'mobile_no' => $data['mobile_no'],
                    'website' => $data['business_website'] ?? '',
                    'address' => $data['business_address'] ?? '',
                    'logo' => $logo['file_name'] ?? null,
                    'business_category_id' => $category->id,
                    'business_sub_category_ids' => $subCategoryIds,
                    'status' => 1,
                    'is_default' => 1,
                ]);
            }

            $business->sub_categories()->sync($subCategoryIds);

            return [$user, $business];
            });
        } catch (\Throwable $exception) {
            if ($logoPath !== null && is_file($logoPath)) {
                @unlink($logoPath);
            }

            throw $exception;
        }

        if (! $user->email_verified_at) {
            $verificationCode = random_int(100000, 999999);
            EmailVerified::create([
                'user_id' => $user->id,
                'code' => $verificationCode,
                'created_at' => now(),
            ]);
            SendVerificationEmailJob::dispatch($user->email, Str::random(60), $user->name, $verificationCode);
        }

        $profile = $businessProfiles->snapshot($user, $business);

        return response()->json([
            'identity' => array_merge($profile['identity'], [
                'business' => $profile['business'],
                'consent_version' => 'adlive-registration-v1',
                'signup_source' => 'adlive',
                'email_verified' => (bool) $user->email_verified_at,
            ]),
        ], Response::HTTP_CREATED);
    }

    private function bypassesEmailVerification(): bool
    {
        return app()->environment('local')
            && filter_var(env('LOCAL_BYPASS_EMAIL_VERIFICATION', false), FILTER_VALIDATE_BOOL);
    }

    private function validationError(string $field, string $message)
    {
        return response()->json([
            'message' => 'Please correct the highlighted fields.',
            'errors' => [$field => [$message]],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @return array{file_name: string, contents: string}|null */
    private function decodeLogo(mixed $logo): ?array
    {
        if ($logo === null) {
            return null;
        }

        if (! is_array($logo) || ! isset($logo['mime'], $logo['data'])) {
            throw ValidationException::withMessages(['logo' => ['The business logo is invalid.']]);
        }

        $contents = base64_decode((string) $logo['data'], true);
        if (! is_string($contents) || strlen($contents) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages(['logo' => ['The business logo must be an image smaller than 2 MB.']]);
        }

        $image = @getimagesizefromstring($contents);
        $mime = is_array($image) ? image_type_to_mime_type($image[2]) : null;
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! is_string($mime) || ! isset($extensions[$mime]) || ! hash_equals($mime, (string) $logo['mime'])) {
            throw ValidationException::withMessages(['logo' => ['The business logo must be a JPG, PNG or WEBP image.']]);
        }

        return [
            'file_name' => Str::uuid()->toString().'.'.$extensions[$mime],
            'contents' => $contents,
        ];
    }

    /** @param array{file_name: string, contents: string}|null $logo */
    private function persistLogo(?array $logo): ?string
    {
        if ($logo === null) {
            return null;
        }

        $directory = public_path('uploads');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('The business logo storage directory is unavailable.');
        }

        $path = $directory.DIRECTORY_SEPARATOR.$logo['file_name'];
        if (file_put_contents($path, $logo['contents'], LOCK_EX) === false) {
            throw new \RuntimeException('The business logo could not be saved.');
        }

        return $path;
    }
}
