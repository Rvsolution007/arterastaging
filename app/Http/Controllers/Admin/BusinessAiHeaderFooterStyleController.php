<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAiHeaderFooterStyle;
use Illuminate\Http\Request;

class BusinessAiHeaderFooterStyleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $styles = BusinessAiHeaderFooterStyle::withCount('customPostTypes')
            ->orderBy('sort_order')->orderBy('name')->paginate(25);
        return view('business_ai.header_footer.index', compact('styles'));
    }

    public function create()
    {
        return view('business_ai.header_footer.form', [
            'style' => new BusinessAiHeaderFooterStyle(['status' => true, 'overlay_enabled' => true]),
        ]);
    }

    public function store(Request $request)
    {
        BusinessAiHeaderFooterStyle::create($this->payload($request));
        return redirect()->route('custom_post_header_footer_styles.index')->with('success', 'Header & Footer Style added.');
    }

    public function edit(BusinessAiHeaderFooterStyle $businessAiHeaderFooterStyle)
    {
        return view('business_ai.header_footer.form', ['style' => $businessAiHeaderFooterStyle]);
    }

    public function update(Request $request, BusinessAiHeaderFooterStyle $businessAiHeaderFooterStyle)
    {
        $businessAiHeaderFooterStyle->update($this->payload($request));
        return redirect()->route('custom_post_header_footer_styles.index')->with('success', 'Header & Footer Style updated for future Custom Posts.');
    }

    public function destroy(BusinessAiHeaderFooterStyle $businessAiHeaderFooterStyle)
    {
        // A Header/Footer Style is mandatory for an active Custom Post Type.
        $businessAiHeaderFooterStyle->customPostTypes()->update(['status' => false]);
        $businessAiHeaderFooterStyle->delete();
        return back()->with('success', 'Header & Footer Style deleted. Linked Custom Post Types were hidden until another style is selected.');
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
            'status' => $request->boolean('status'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
