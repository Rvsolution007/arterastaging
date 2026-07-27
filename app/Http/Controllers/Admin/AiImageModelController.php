<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiImageModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiImageModelController extends Controller
{
    private const QUALITY_OPTIONS = [
        'low' => 'Fast (Low)',
        'medium' => 'Standard (Medium)',
        'high' => 'High Quality',
        'auto' => 'Auto (provider chooses)',
        'standard' => 'Standard',
        'hd' => 'HD',
    ];

    private const SIZE_OPTIONS = [
        'square' => ['label' => 'Square Post', 'ratio' => '1:1', 'size' => '1024x1024'],
        'landscape' => ['label' => 'Landscape Post', 'ratio' => '16:9', 'size' => '1536x1024'],
        'portrait' => ['label' => 'Story / Portrait', 'ratio' => '9:16', 'size' => '1024x1536'],
    ];

    public function __construct()
    {
        $this->middleware('permission:Settings');
    }

    public function index()
    {
        return view('backend.ai_image_models.index', [
            'models' => AiImageModel::orderByDesc('is_recommended')->orderBy('sort_order')->orderBy('id')->get(),
            'qualityOptions' => self::QUALITY_OPTIONS,
            'sizeOptions' => self::SIZE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        AiImageModel::create($this->payload($request));

        return back()->with('success', 'AI image model added. It will stay hidden from the app until it is active and assigned to a plan.');
    }

    public function update(Request $request, AiImageModel $aiImageModel)
    {
        $aiImageModel->update($this->payload($request, $aiImageModel->id));

        return back()->with('success', 'AI image model updated.');
    }

    private function payload(Request $request, ?int $ignoreId = null): array
    {
        $uniqueModel = Rule::unique('ai_image_models', 'model_id')
            ->where(fn ($query) => $query->where('provider', $request->input('provider', 'openai')));

        if ($ignoreId !== null) {
            $uniqueModel->ignore($ignoreId);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'model_id' => ['required', 'string', 'max:100', $uniqueModel],
            'display_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'quality_options' => ['required', 'array', 'min:1'],
            'quality_options.*' => ['string', Rule::in(array_keys(self::QUALITY_OPTIONS))],
            'quality_display_names' => ['nullable', 'array'],
            'quality_display_names.*' => ['nullable', 'string', 'max:80'],
            'default_quality' => ['required', 'string', Rule::in(array_keys(self::QUALITY_OPTIONS))],
            'size_keys' => ['required', 'array', 'min:1'],
            'size_keys.*' => ['string', Rule::in(array_keys(self::SIZE_OPTIONS))],
            'default_size_key' => ['required', 'string', Rule::in(array_keys(self::SIZE_OPTIONS))],
            'max_reference_images' => ['required', 'integer', 'min:0', 'max:10'],
            'estimated_seconds' => ['nullable', 'integer', 'min:1', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'pricing_config' => ['nullable', 'array'],
            'pricing_config.input_per_million_usd' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'pricing_config.output_per_million_usd' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'pricing_config.image_per_unit_usd' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'pricing_config.usd_to_inr' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        if (!in_array($validated['default_quality'], $validated['quality_options'], true)) {
            throw ValidationException::withMessages([
                'default_quality' => 'Default quality must be one of this model\'s enabled qualities.',
            ]);
        }

        if (!in_array($validated['default_size_key'], $validated['size_keys'], true)) {
            throw ValidationException::withMessages([
                'default_size_key' => 'Default size must be one of this model\'s enabled sizes.',
            ]);
        }

        $selectedQualities = array_values(array_unique($validated['quality_options']));
        $qualityDisplayNames = collect($selectedQualities)->mapWithKeys(function (string $quality) use ($validated) {
            $name = trim((string) data_get($validated, 'quality_display_names.' . $quality, ''));

            return [$quality => $name !== '' ? $name : self::QUALITY_OPTIONS[$quality]];
        })->all();

        return [
            'provider' => $validated['provider'],
            'model_id' => trim($validated['model_id']),
            'display_name' => trim($validated['display_name']),
            'description' => $validated['description'] ?? null,
            'quality_options' => $selectedQualities,
            'quality_display_names' => $qualityDisplayNames,
            'size_options' => array_values(array_map(
                fn (string $key) => array_merge(['key' => $key], self::SIZE_OPTIONS[$key]),
                array_values(array_unique($validated['size_keys']))
            )),
            'default_quality' => $validated['default_quality'],
            'default_size_key' => $validated['default_size_key'],
            'supports_reference_images' => $request->boolean('supports_reference_images'),
            'supports_edits' => $request->boolean('supports_edits'),
            'supports_transparent_background' => $request->boolean('supports_transparent_background'),
            'max_reference_images' => $validated['max_reference_images'],
            'estimated_seconds' => $validated['estimated_seconds'] ?? null,
            'pricing_config' => $this->pricingConfig($validated['pricing_config'] ?? []),
            'is_active' => $request->boolean('is_active'),
            'is_recommended' => $request->boolean('is_recommended'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }

    private function pricingConfig(array $pricing): array
    {
        return [
            'input_per_million_usd' => (float) ($pricing['input_per_million_usd'] ?? 0),
            'output_per_million_usd' => (float) ($pricing['output_per_million_usd'] ?? 0),
            'image_per_unit_usd' => (float) ($pricing['image_per_unit_usd'] ?? 0),
            'usd_to_inr' => (float) ($pricing['usd_to_inr'] ?? 90),
        ];
    }
}
