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
