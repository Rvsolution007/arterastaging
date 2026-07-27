<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FestivalAiStyle;
use App\Models\FestivalAiStylePreset;
use App\Models\StorageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FestivalAiStylePresetController extends Controller
{
    private const SIZE_OPTIONS = [
        'square' => 'Square Post (1:1)',
        'landscape' => 'Landscape Post (16:9)',
        'portrait' => 'Story / Portrait (9:16)',
    ];

    public function __construct()
    {
        $this->middleware('permission:Festival');
    }

    public function index()
    {
        $presets = FestivalAiStylePreset::withCount([
            'festivalAssignments as active_festival_assignments_count' => fn ($query) => $query->where('status', true),
        ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);

        return view('festivals.ai_style_presets.index', compact('presets'));
    }

    public function create()
    {
        return view('festivals.ai_style_presets.form', [
            'preset' => new FestivalAiStylePreset(['status' => true]),
            'sizeOptions' => self::SIZE_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePreset($request, true);

        FestivalAiStylePreset::create([
            'name' => trim($validated['name']),
            'prompt_text' => $validated['prompt_text'],
            'preview_images' => $this->storePreviewImages($request->file('preview_images', [])),
            'allowed_size_keys' => $this->normaliseSizeKeys($validated['allowed_size_keys'] ?? null),
            'product_required' => $request->boolean('product_required'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('festival_ai_styles.index')
            ->with('success', 'Festival Style added to the central library.');
    }

    public function edit(FestivalAiStylePreset $festivalAiStyle)
    {
        $festivalAiStyle->preview_urls = array_map(
            fn (string $path) => $this->previewUrl($path),
            $festivalAiStyle->preview_images ?? []
        );

        return view('festivals.ai_style_presets.form', [
            'preset' => $festivalAiStyle,
            'sizeOptions' => self::SIZE_OPTIONS,
        ]);
    }

    public function update(Request $request, FestivalAiStylePreset $festivalAiStyle)
    {
        $validated = $this->validatePreset($request, false);
        $remove = array_values(array_intersect(
            $festivalAiStyle->preview_images ?? [],
            $request->input('remove_preview_images', [])
        ));
        $remaining = array_values(array_diff($festivalAiStyle->preview_images ?? [], $remove));
        $newFiles = $request->file('preview_images', []);

        if (count($remaining) + count($newFiles) < 1) {
            throw ValidationException::withMessages([
                'preview_images' => 'A Festival Style needs at least one preview image.',
            ]);
        }

        if (count($remaining) + count($newFiles) > 3) {
            throw ValidationException::withMessages([
                'preview_images' => 'A Festival Style can have a maximum of three preview images.',
            ]);
        }

        if (!empty($remove)) {
            $this->deletePreviewImages($remove);
        }

        $payload = [
            'name' => trim($validated['name']),
            'prompt_text' => $validated['prompt_text'],
            'preview_images' => array_merge($remaining, $this->storePreviewImages($newFiles)),
            'allowed_size_keys' => $this->normaliseSizeKeys($validated['allowed_size_keys'] ?? null),
            'product_required' => $request->boolean('product_required'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $request->boolean('status'),
        ];

        $festivalAiStyle->update($payload);

        // A selected central style is the source of truth for every festival
        // that uses it, so its prompt and visual rules stay consistent.
        FestivalAiStyle::where('festival_ai_style_preset_id', $festivalAiStyle->id)
            ->update($this->assignmentPayload($payload));

        return redirect()->route('festival_ai_styles.index')
            ->with('success', 'Festival Style updated for every selected festival.');
    }

    public function destroy(FestivalAiStylePreset $festivalAiStyle)
    {
        // Keep old generation history intact, but immediately hide the style
        // from every festival and disconnect it from the deleted library row.
        FestivalAiStyle::where('festival_ai_style_preset_id', $festivalAiStyle->id)
            ->update([
                'festival_ai_style_preset_id' => null,
                'status' => false,
            ]);

        $this->deletePreviewImages($festivalAiStyle->preview_images ?? []);
        $festivalAiStyle->delete();

        return back()->with('success', 'Festival Style deleted and removed from all festivals.');
    }

    private function validatePreset(Request $request, bool $new): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'prompt_text' => ['required', 'string', 'max:10000'],
            'allowed_size_keys' => ['nullable', 'array'],
            'allowed_size_keys.*' => ['string', Rule::in(array_keys(self::SIZE_OPTIONS))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'preview_images' => [$new ? 'required' : 'nullable', 'array', $new ? 'min:1' : 'max:3', 'max:3'],
            'preview_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_preview_images' => ['nullable', 'array'],
            'remove_preview_images.*' => ['string', 'max:255'],
        ]);
    }

    private function normaliseSizeKeys(?array $keys): ?array
    {
        return $keys ? array_values(array_unique($keys)) : null;
    }

    private function storePreviewImages(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $relativePath = 'festival-ai-styles/' . $name;

            if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
                Storage::disk('spaces')->put('uploads/' . $relativePath, file_get_contents($file->getRealPath()), 'public');
            } else {
                File::ensureDirectoryExists(public_path('uploads/festival-ai-styles'));
                $file->move(public_path('uploads/festival-ai-styles'), $name);
            }

            $paths[] = $relativePath;
        }

        return $paths;
    }

    private function deletePreviewImages(array $paths): void
    {
        foreach ($paths as $path) {
            if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
                Storage::disk('spaces')->delete('uploads/' . $path);
                continue;
            }

            $file = public_path('uploads/' . $path);
            if (is_file($file)) {
                File::delete($file);
            }
        }
    }

    private function previewUrl(string $path): string
    {
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            return Storage::disk('spaces')->url('uploads/' . $path);
        }

        return asset('uploads/' . $path);
    }

    private function assignmentPayload(array $payload): array
    {
        // Query-builder updates bypass Eloquent casts, so JSON fields must be
        // encoded before synchronising a central style to selected festivals.
        $payload['preview_images'] = json_encode($payload['preview_images']);
        $payload['allowed_size_keys'] = $payload['allowed_size_keys'] === null
            ? null
            : json_encode($payload['allowed_size_keys']);

        return $payload;
    }
}
