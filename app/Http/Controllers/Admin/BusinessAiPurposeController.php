<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAiHeaderFooterStyle;
use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiStyle;
use App\Models\CustomFramePurpose;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Custom Post Type studio: prompt, type rules, header/footer and linked styles. */
class BusinessAiPurposeController extends Controller
{
    private const SIZE_OPTIONS = [
        'square' => 'Square Post (1:1)',
        'landscape' => 'Landscape Post (16:9)',
        'portrait' => 'Story / Portrait (9:16)',
    ];

    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $purposes = BusinessAiPurpose::with(['headerFooterStyle:id,name'])->withCount('styles')
            ->orderBy('sort_order')->orderBy('title')->paginate(25);
        // ZIP Custom Posts use a legacy purpose table because each record is
        // referenced by stored ZIP templates and its separate AI batch prompt.
        // Show/manage it here with AI Types, but keep the data contracts apart.
        $zipPurposes = CustomFramePurpose::withCount('frames')->orderByDesc('id')->get();
        $dynamicTags = [
            '{col_is_category}',
            '{col_is_unique}',
            '{col_is_combo}',
            '{col_is_Normal Regular Field}',
        ];

        return view('business_ai.purposes.index', compact('purposes', 'zipPurposes', 'dynamicTags'));
    }

    public function create()
    {
        return $this->formResponse(new BusinessAiPurpose([
            'status' => true,
            'product_upload_enabled' => true,
            'max_product_references' => 4,
            'change_instruction_limit' => 300,
            'allowed_size_keys' => array_keys(self::SIZE_OPTIONS),
            'brief_fields' => [['key' => '', 'label' => '', 'hint' => '', 'required' => false]],
        ]));
    }

    public function store(Request $request)
    {
        $validated = $this->validateType($request);
        $this->assertEnabledTypeIsComplete($request, $validated);
        $type = BusinessAiPurpose::create($this->payload($request, $validated, $this->nextKey($validated['title'])));
        $type->styles()->sync($this->styleIds($validated));

        return redirect()->route('custom_post_types.index')
            ->with('success', 'Custom Post Type, prompt and app rules were added.');
    }

    public function edit(BusinessAiPurpose $businessAiPurpose)
    {
        return $this->formResponse($businessAiPurpose);
    }

    public function update(Request $request, BusinessAiPurpose $businessAiPurpose)
    {
        $validated = $this->validateType($request);
        $this->assertEnabledTypeIsComplete($request, $validated);
        // Keep the internal key stable, so generation history and user drafts
        // still point to the same Type after a visible title rename.
        $businessAiPurpose->update($this->payload($request, $validated, $businessAiPurpose->key));
        $businessAiPurpose->styles()->sync($this->styleIds($validated));

        return redirect()->route('custom_post_types.index')
            ->with('success', 'Custom Post Type updated. The mobile app will use it on its next refresh.');
    }

    public function destroy(BusinessAiPurpose $businessAiPurpose)
    {
        $businessAiPurpose->delete();

        return back()->with('success', 'Custom Post Type deleted. Its reusable styles remain in the Style library.');
    }

    private function formResponse(BusinessAiPurpose $type)
    {
        return view('business_ai.purposes.form', [
            'purpose' => $type,
            'sizeOptions' => self::SIZE_OPTIONS,
            'headerFooterStyles' => BusinessAiHeaderFooterStyle::where('status', true)->orderBy('sort_order')->orderBy('name')->get(),
            'styleLibrary' => BusinessAiStyle::where('status', true)->orderBy('sort_order')->orderBy('name')->get(),
            'selectedStyleIds' => $type->exists ? $type->styles()->pluck('business_ai_styles.id')->map(fn ($id) => (int) $id)->all() : [],
        ]);
    }

    private function validateType(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:300'],
            'base_prompt' => ['required', 'string', 'max:10000'],
            'product_prompt' => ['nullable', 'string', 'max:3000'],
            'business_ai_header_footer_style_id' => ['nullable', 'integer', Rule::exists('business_ai_header_footer_styles', 'id')->where('status', true)],
            'style_ids' => ['nullable', 'array'],
            'style_ids.*' => ['integer', 'distinct', Rule::exists('business_ai_styles', 'id')->where('status', true)],
            'brief_fields' => ['required', 'array', 'min:1', 'max:20'],
            'brief_fields.*.key' => ['required', 'string', 'max:50', 'alpha_dash', 'distinct'],
            'brief_fields.*.label' => ['required', 'string', 'max:150'],
            'brief_fields.*.hint' => ['nullable', 'string', 'max:200'],
            'brief_fields.*.required' => ['nullable', 'boolean'],
            'allowed_size_keys' => ['required', 'array', 'min:1'],
            'allowed_size_keys.*' => ['string', Rule::in(array_keys(self::SIZE_OPTIONS))],
            'max_product_references' => ['required', 'integer', 'min:1', 'max:4'],
            'change_instruction_limit' => ['required', 'integer', 'min:50', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function assertEnabledTypeIsComplete(Request $request, array $validated): void
    {
        if (!$request->boolean('status')) {
            return;
        }
        if (empty($validated['style_ids'] ?? [])) {
            throw ValidationException::withMessages(['style_ids' => 'Select at least one Custom Post Style before making this Type active.']);
        }
        if (empty($validated['business_ai_header_footer_style_id'])) {
            throw ValidationException::withMessages(['business_ai_header_footer_style_id' => 'Select a Header & Footer Style before making this Type active.']);
        }
    }

    private function payload(Request $request, array $validated, string $key): array
    {
        return [
            'key' => $key,
            'title' => trim($validated['title']),
            'icon' => blank($validated['icon'] ?? null) ? null : trim($validated['icon']),
            'description' => blank($validated['description'] ?? null) ? null : trim($validated['description']),
            'base_prompt' => trim($validated['base_prompt']),
            'product_prompt' => blank($validated['product_prompt'] ?? null) ? null : trim($validated['product_prompt']),
            'brief_fields' => $this->normaliseFields($validated['brief_fields']),
            'business_ai_header_footer_style_id' => $validated['business_ai_header_footer_style_id'] ?? null,
            'allowed_size_keys' => array_values(array_unique($validated['allowed_size_keys'])),
            'product_upload_enabled' => $request->boolean('product_upload_enabled'),
            'product_required' => $request->boolean('product_upload_enabled') && $request->boolean('product_required'),
            'max_product_references' => $validated['max_product_references'],
            'change_instruction_limit' => $validated['change_instruction_limit'],
            'status' => $request->boolean('status'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }

    private function normaliseFields(array $fields): array
    {
        return collect($fields)->map(fn (array $field) => [
            'key' => Str::lower(trim($field['key'])),
            'label' => trim($field['label']),
            'hint' => blank($field['hint'] ?? null) ? null : trim($field['hint']),
            'required' => (bool) ($field['required'] ?? false),
        ])->values()->all();
    }

    private function styleIds(array $validated): array
    {
        return array_values(array_unique(array_map('intval', $validated['style_ids'] ?? [])));
    }

    private function nextKey(string $title): string
    {
        $base = Str::limit(Str::slug($title, '_'), 70, '') ?: 'custom_post_type';
        $key = $base;
        $suffix = 2;
        while (BusinessAiPurpose::where('key', $key)->exists()) {
            $key = Str::limit($base, 70, '') . '_' . $suffix++;
        }
        return $key;
    }
}
