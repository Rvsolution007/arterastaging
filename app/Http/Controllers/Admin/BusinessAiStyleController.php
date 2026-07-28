<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAiStyle;
use App\Models\StorageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Reusable visual style library selected from each Custom Post Type. */
class BusinessAiStyleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $styles = BusinessAiStyle::withCount('purposes')->orderBy('sort_order')->orderBy('name')->paginate(25);
        return view('business_ai.styles.index', compact('styles'));
    }

    public function create()
    {
        return view('business_ai.styles.form', [
            'style' => new BusinessAiStyle(['status' => true, 'colors' => ['#4338CA', '#0F172A']]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateStyle($request);
        BusinessAiStyle::create($this->payload($request, $validated, $this->nextKey($validated['name'])));
        return redirect()->route('custom_post_styles.index')->with('success', 'Custom Post Style and its Style Prompt were added.');
    }

    public function edit(BusinessAiStyle $businessAiStyle)
    {
        $businessAiStyle->preview_image_url = $this->previewUrl($businessAiStyle->preview_image);
        return view('business_ai.styles.form', ['style' => $businessAiStyle]);
    }

    public function update(Request $request, BusinessAiStyle $businessAiStyle)
    {
        $validated = $this->validateStyle($request);
        $newPreview = $this->storePreviewImage($request);
        if ($newPreview && $businessAiStyle->preview_image) {
            $this->deletePreviewImage($businessAiStyle->preview_image);
        }
        $businessAiStyle->update($this->payload($request, $validated, $businessAiStyle->key, $newPreview ?: $businessAiStyle->preview_image));
        return redirect()->route('custom_post_styles.index')->with('success', 'Custom Post Style updated.');
    }

    public function destroy(BusinessAiStyle $businessAiStyle)
    {
        // An active Type without any selectable style must not reach the app.
        $businessAiStyle->purposes()->update(['status' => false]);
        $this->deletePreviewImage($businessAiStyle->preview_image);
        $businessAiStyle->delete();
        return back()->with('success', 'Custom Post Style deleted. Linked Custom Post Types were hidden until a replacement style is selected.');
    }

    private function validateStyle(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
            'prompt_text' => ['required', 'string', 'max:10000'],
            'primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'preview_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function payload(Request $request, array $validated, string $key, ?string $previewImage = null): array
    {
        return [
            'key' => $key,
            'name' => trim($validated['name']),
            'description' => blank($validated['description'] ?? null) ? null : trim($validated['description']),
            'prompt_text' => trim($validated['prompt_text']),
            'colors' => [$validated['primary_color'], $validated['secondary_color']],
            'preview_image' => $previewImage ?? $this->storePreviewImage($request),
            'status' => $request->boolean('status'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }

    private function nextKey(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 70, '') ?: 'style';
        $key = $base;
        $suffix = 2;
        while (BusinessAiStyle::where('key', $key)->exists()) $key = Str::limit($base, 70, '') . '_' . $suffix++;
        return $key;
    }

    private function storePreviewImage(Request $request): ?string
    {
        $file = $request->file('preview_image');
        if (!$file) return null;
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = 'custom-post-styles/' . $name;
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') Storage::disk('spaces')->put('uploads/' . $path, file_get_contents($file->getRealPath()), 'public');
        else { File::ensureDirectoryExists(public_path('uploads/custom-post-styles')); $file->move(public_path('uploads/custom-post-styles'), $name); }
        return $path;
    }

    private function deletePreviewImage(?string $path): void
    {
        if (!$path) return;
        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') { Storage::disk('spaces')->delete('uploads/' . $path); return; }
        $file = public_path('uploads/' . $path);
        if (is_file($file)) File::delete($file);
    }

    private function previewUrl(?string $path): ?string
    {
        if (!$path) return null;
        return StorageSetting::getStorageSetting('storage') === 'DigitalOcean' ? Storage::disk('spaces')->url('uploads/' . $path) : asset('uploads/' . $path);
    }
}
