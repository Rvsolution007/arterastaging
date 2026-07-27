<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FestivalAiBrandChromePreset;
use Illuminate\Http\Request;

class FestivalAiBrandChromePresetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Festival');
    }

    public function index()
    {
        $presets = FestivalAiBrandChromePreset::withCount('festivalConfigs')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25);

        return view('festivals.ai_brand_chrome_presets.index', compact('presets'));
    }

    public function create()
    {
        return view('festivals.ai_brand_chrome_presets.form', [
            'preset' => new FestivalAiBrandChromePreset([
                'status' => true,
                'overlay_enabled' => true,
                'header_height_percent' => 12,
                'footer_height_percent' => 10,
                'panel_style' => 'adaptive',
                'logo_position' => 'left',
                'text_tone' => 'auto',
                'max_contact_items' => 4,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        FestivalAiBrandChromePreset::create($this->payload($request));

        return redirect()->route('festival_ai_brand_chrome.index')
            ->with('success', 'Header & Footer Style added to the central library.');
    }

    public function edit(FestivalAiBrandChromePreset $festivalAiBrandChrome)
    {
        return view('festivals.ai_brand_chrome_presets.form', [
            'preset' => $festivalAiBrandChrome,
        ]);
    }

    public function update(Request $request, FestivalAiBrandChromePreset $festivalAiBrandChrome)
    {
        $festivalAiBrandChrome->update($this->payload($request));

        return redirect()->route('festival_ai_brand_chrome.index')
            ->with('success', 'Header & Footer Style updated. Future Festival AI generations will use the new prompt.');
    }

    public function destroy(FestivalAiBrandChromePreset $festivalAiBrandChrome)
    {
        // Existing generated images retain their own immutable prompt snapshot.
        $festivalAiBrandChrome->delete();

        return back()->with('success', 'Header & Footer Style deleted. Festivals using it will return to standard AI visuals until another style is selected.');
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'header_prompt' => ['nullable', 'string', 'max:5000'],
            'footer_prompt' => ['nullable', 'string', 'max:5000'],
            'header_height_percent' => ['required', 'integer', 'min:6', 'max:20'],
            'footer_height_percent' => ['required', 'integer', 'min:6', 'max:20'],
            'panel_style' => ['required', 'string', 'in:adaptive,light,dark,none'],
            'logo_position' => ['required', 'string', 'in:left,right'],
            'text_tone' => ['required', 'string', 'in:auto,light,dark'],
            'max_contact_items' => ['required', 'integer', 'min:1', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'name' => trim($validated['name']),
            'header_prompt' => blank($validated['header_prompt'] ?? null) ? null : trim($validated['header_prompt']),
            'footer_prompt' => blank($validated['footer_prompt'] ?? null) ? null : trim($validated['footer_prompt']),
            'overlay_enabled' => $request->boolean('overlay_enabled'),
            'header_height_percent' => $validated['header_height_percent'],
            'footer_height_percent' => $validated['footer_height_percent'],
            'panel_style' => $validated['panel_style'],
            'logo_position' => $validated['logo_position'],
            'text_tone' => $validated['text_tone'],
            'max_contact_items' => $validated['max_contact_items'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $request->boolean('status'),
        ];
    }
}
