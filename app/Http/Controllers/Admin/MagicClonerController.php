<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MagicClonerController extends Controller
{
    public function __construct()
    {
        // Add permissions later if needed, assuming admin access.
        $this->middleware('auth');
    }

    public function index()
    {
        $defaultPrompt = 'Act as an expert graphic designer. Analyze this marketing image and extract the visual design system. Return a strict JSON containing the requested mapping keys. Follow the precise format requested. Analyze the contrast and visual hierarchy accurately. DO NOT wrap the json in markdown backticks.';
        
        $prompt = \App\Models\MagicClonerSetting::getSetting('ai_prompt', $defaultPrompt);
        $mapping = \App\Models\MagicClonerSetting::getSetting('mapping_rules', json_encode([
            [
                'ai_key' => 'ai_primary_color',
                'json_target' => 'objects[type=rect].fill',
                'description' => 'Image mein jo background/dominant color hai, wo JSON templates ke shapes me overwrite hoga.'
            ],
            [
                'ai_key' => 'ai_secondary_color',
                'json_target' => 'objects[type=textbox].fill',
                'description' => 'Text aur highlights secondary color le lenge taaki contrast barkarar rahe.'
            ],
            [
                'ai_key' => 'ai_font_vibe',
                'json_target' => 'objects[type=textbox].fontFamily',
                'description' => 'AI bataega if font bold/formal/script hai. System usko local fonts se map karega.'
            ],
            [
                'ai_key' => 'ai_layout_style',
                'json_target' => 'CustomPostFrame.tags',
                'description' => 'Jo layout AI batayega (e.g., minimalist), DB se wahi matching tag wali zip file uthayi jayengi.'
            ]
        ]));

        return view('magic_cloner.index', compact('prompt', 'mapping'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ai_prompt' => 'required',
            'mapping_rules' => 'required|json',
        ]);

        \App\Models\MagicClonerSetting::updateOrCreate(
            ['key_name' => 'ai_prompt'],
            ['key_value' => $request->ai_prompt]
        );

        \App\Models\MagicClonerSetting::updateOrCreate(
            ['key_name' => 'mapping_rules'],
            ['key_value' => $request->mapping_rules]
        );

        return redirect()->back()->with('success', 'Magic Cloner Settings updated successfully!');
    }
}
