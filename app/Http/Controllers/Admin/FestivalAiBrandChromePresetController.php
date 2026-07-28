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
            ]),
        ]);
    }

    public function store(Request $request)
    {
        FestivalAiBrandChromePreset::create($this->payload($request));

        return redirect()->route('festival_ai_brand_chrome.index')
            ->with('success', 'Business branding prompt added to the central library.');
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
            ->with('success', 'Business branding prompt updated. Future Festival AI generations will use it.');
    }

    public function destroy(FestivalAiBrandChromePreset $festivalAiBrandChrome)
    {
        // Existing generated images retain their own immutable prompt snapshot.
        $festivalAiBrandChrome->delete();

        return back()->with('success', 'Business branding prompt deleted. Festivals using it will return to standard AI visuals until another prompt is selected.');
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'header_prompt' => ['nullable', 'string', 'max:5000'],
            'footer_prompt' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'name' => trim($validated['name']),
            'header_prompt' => blank($validated['header_prompt'] ?? null) ? null : trim($validated['header_prompt']),
            'footer_prompt' => blank($validated['footer_prompt'] ?? null) ? null : trim($validated['footer_prompt']),
            'overlay_enabled' => $request->boolean('overlay_enabled'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $request->boolean('status'),
        ];
    }
}
