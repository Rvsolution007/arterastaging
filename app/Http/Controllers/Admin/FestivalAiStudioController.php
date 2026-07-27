<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FestivalAiConfig;
use App\Models\FestivalAiBrandChromePreset;
use App\Models\FestivalAiStyle;
use App\Models\FestivalAiStylePreset;
use App\Models\Festivals;
use App\Services\FestivalAiPromptCompiler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FestivalAiStudioController extends Controller
{
    private const SIZE_OPTIONS = [
        'square' => 'Square Post (1:1)',
        'landscape' => 'Landscape Post (16:9)',
        'portrait' => 'Story / Portrait (9:16)',
    ];

    public function __construct(private FestivalAiPromptCompiler $promptCompiler)
    {
        $this->middleware('permission:Festival');
    }

    public function edit(Festivals $festival)
    {
        $config = FestivalAiConfig::with(['styles', 'brandChromePreset'])
            ->where('festival_id', $festival->id)
            ->first();
        if (!$config) {
            $config = new FestivalAiConfig([
                'festival_id' => $festival->id,
                'allow_product_upload' => true,
                'require_product_name_for_upload' => true,
                'max_products' => 3,
                'max_user_instruction_characters' => 250,
                'allowed_size_keys' => array_keys(self::SIZE_OPTIONS),
            ]);
        }

        $styles = $config->exists ? $config->styles : collect();
        $stylePresets = FestivalAiStylePreset::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $brandChromePresets = FestivalAiBrandChromePreset::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $selectedStylePresetIds = $styles
            ->where('status', true)
            ->pluck('festival_ai_style_preset_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
        $promptAudit = $this->promptCompiler->audit(
            $config->base_prompt,
            $styles->where('status', true)->pluck('prompt_text')->implode("\n"),
            $config->product_prompt,
            implode("\n", array_filter([
                optional($config->brandChromePreset)->header_prompt,
                optional($config->brandChromePreset)->footer_prompt,
            ])),
            $festival->title
        );

        return view('festivals.ai_studio', compact(
            'festival',
            'config',
            'styles',
            'stylePresets',
            'brandChromePresets',
            'selectedStylePresetIds',
            'promptAudit'
        ))->with('sizeOptions', self::SIZE_OPTIONS);
    }

    public function update(Festivals $festival, Request $request)
    {
        $validated = $request->validate([
            'base_prompt' => ['nullable', 'string', 'max:10000'],
            'product_prompt' => ['nullable', 'string', 'max:3000'],
            'festival_ai_brand_chrome_preset_id' => [
                'nullable',
                'integer',
                Rule::exists('festival_ai_brand_chrome_presets', 'id')->where('status', true),
            ],
            'allowed_size_keys' => ['required', 'array', 'min:1'],
            'allowed_size_keys.*' => ['string', Rule::in(array_keys(self::SIZE_OPTIONS))],
            'max_products' => ['required', 'integer', 'min:1', 'max:3'],
            'max_user_instruction_characters' => ['required', 'integer', 'min:50', 'max:1000'],
            'style_preset_ids' => ['nullable', 'array'],
            'style_preset_ids.*' => [
                'integer',
                Rule::exists('festival_ai_style_presets', 'id')->where('status', true),
            ],
        ]);

        $isEnabled = $request->boolean('is_enabled');
        $stylePresetIds = array_values(array_unique(array_map(
            'intval',
            $validated['style_preset_ids'] ?? []
        )));

        if ($isEnabled && blank($validated['base_prompt'] ?? null)) {
            throw ValidationException::withMessages([
                'base_prompt' => 'Festival AI can only be enabled after its Festival Prompt is added.',
            ]);
        }

        if ($isEnabled && empty($stylePresetIds)) {
            throw ValidationException::withMessages([
                'style_preset_ids' => 'Select at least one Festival Style before enabling Festival AI.',
            ]);
        }

        $config = FestivalAiConfig::updateOrCreate(
            ['festival_id' => $festival->id],
            [
                'is_enabled' => $isEnabled,
                'festival_ai_brand_chrome_preset_id' => $validated['festival_ai_brand_chrome_preset_id'] ?? null,
                'base_prompt' => $validated['base_prompt'] ?? null,
                'product_prompt' => $validated['product_prompt'] ?? null,
                'allowed_size_keys' => array_values(array_unique($validated['allowed_size_keys'])),
                'max_products' => $validated['max_products'],
                'allow_product_upload' => $request->boolean('allow_product_upload'),
                'require_product_name_for_upload' => $request->boolean('require_product_name_for_upload'),
                'max_user_instruction_characters' => $validated['max_user_instruction_characters'],
            ]
        );

        $this->syncStylePresets($config, $stylePresetIds);

        $config->load(['styles', 'brandChromePreset']);
        $promptAudit = $this->promptCompiler->audit(
            $config->base_prompt,
            $config->styles->where('status', true)->pluck('prompt_text')->implode("\n"),
            $config->product_prompt,
            implode("\n", array_filter([
                optional($config->brandChromePreset)->header_prompt,
                optional($config->brandChromePreset)->footer_prompt,
            ])),
            $festival->title
        );

        return back()
            ->with('success', 'Festival AI settings and selected styles saved.')
            ->with('prompt_warnings', $promptAudit);
    }

    private function syncStylePresets(FestivalAiConfig $config, array $presetIds): void
    {
        $presets = FestivalAiStylePreset::query()
            ->whereIn('id', $presetIds)
            ->where('status', true)
            ->get()
            ->keyBy('id');

        $existingStyles = $config->styles()
            ->whereNotNull('festival_ai_style_preset_id')
            ->get()
            ->keyBy('festival_ai_style_preset_id');

        foreach ($presets as $preset) {
            $payload = [
                'name' => $preset->name,
                'prompt_text' => $preset->prompt_text,
                'product_placement_prompt' => $preset->product_placement_prompt,
                'preview_images' => $preset->preview_images,
                'allowed_size_keys' => $preset->allowed_size_keys,
                'product_required' => $preset->product_required,
                'sort_order' => $preset->sort_order,
                'status' => $preset->status,
            ];

            $style = $existingStyles->get($preset->id);
            if ($style) {
                $style->update($payload);
                continue;
            }

            FestivalAiStyle::create([
                'festival_ai_config_id' => $config->id,
                'festival_ai_style_preset_id' => $preset->id,
                ...$payload,
            ]);
        }

        $config->styles()
            ->whereNotNull('festival_ai_style_preset_id')
            ->whereNotIn('festival_ai_style_preset_id', $presetIds)
            ->update(['status' => false]);
    }
}
