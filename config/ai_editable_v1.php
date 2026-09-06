<?php

return [
    /*
     * This flag is intentionally independent from Festival AI and the frame
     * renderer.  Keeping the feature disabled is a clean, zero-regression
     * rollback: existing generation, preview and frame flows never branch
     * into this module unless an administrator explicitly enables it.
     */
    'enabled' => filter_var(env('AI_EDITABLE_V1_ENABLED', false), FILTER_VALIDATE_BOOL),

    // A text-capable OpenAI model used only to turn a poster prompt into the
    // V1 layer plan. Leave this empty until the model has been selected and
    // tested for the deployment; the mobile option remains hidden meanwhile.
    'planner_model' => trim((string) env('AI_EDITABLE_V1_PLANNER_MODEL', '')),
    'contract' => 'artera.ai-editable/v1',
    'schema_version' => 1,
    // V2 is used only by new Business/Custom Post AI generations. V1 remains
    // fully readable for existing Festival and Business documents.
    'business_custom_contract' => 'artera.ai-editable/v2',
    'contracts' => [
        'artera.ai-editable/v1' => [
            'schema_version' => 1,
            'module_version' => 'ai_editable_v1',
            'layer_types' => ['bitmap', 'text', 'gradient', 'shape', 'icon', 'effect'],
            'text_only' => false,
        ],
        'artera.ai-editable/v2' => [
            'schema_version' => 2,
            'module_version' => 'ai_editable_v2',
            'layer_types' => ['bitmap', 'text'],
            'text_only' => true,
        ],
    ],
    'max_layers' => 32,
    'max_canvas_dimension' => 8192,

    // V1 deliberately uses ordinary alpha compositing.  This keeps the mobile
    // preview and exported PNG identical. Blend modes need a dedicated
    // canvas-compositor and will be introduced only in a later contract
    // version, never silently approximated by the editor.
    'blend_modes' => [
        'normal',
    ],

    'layer_types' => [
        'bitmap',
        'text',
        'gradient',
        'shape',
        'icon',
        'effect',
    ],
];
