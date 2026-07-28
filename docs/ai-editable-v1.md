# AI Editable V1

AI Editable V1 is an opt-in, frame-free document flow. It does not use the
existing `render_version` contract and it does not change a Festival AI
generation's normal flat-image status, quota, preview, or frame editor.

## Enable it

1. Deploy the two `2026_07_28` database migrations.
2. Configure the existing ChatGPT API key in Artera's AI settings.
3. Set `AI_EDITABLE_V1_ENABLED=true` and provide a Responses API model in
   `AI_EDITABLE_V1_PLANNER_MODEL`.
4. Rebuild Laravel's config/route cache and keep the existing `festival-ai`
   queue worker running.

When the flag or planner model is missing, mobile hides the option completely.
This is the rollback switch: set the flag back to `false`; existing flat AI
generation and every existing editor continues as before.

## What the generation does

The normal Festival visual is generated first. If the customer selected
**Editable AI layers**, a second independent job creates a V1 manifest:

- native gradient layer;
- independent background bitmap;
- up to three transparent foreground bitmaps;
- native text layers using approved font tokens;
- editable glow, blur, shadow, opacity, position, size, rotation and layer
  ordering.

The mobile creation card opens the separate AI editable editor once its
document is ready. It has no frame control and never loads the existing native
editor or its frame/template state.

## Important quality boundary

An arbitrary finished JPG cannot be decomposed back into its original design
layers with guaranteed fidelity. V1 avoids that problem by composing the
background, foregrounds, effects and text as separate source assets from the
start. The ordinary flat PNG/JPG is still available as a safe fallback if the
optional V1 job fails.

Fonts are stored as approved tokens rather than unlicensed provider font
names. V1 currently maps them to Hind, Poppins, Playfair Display and Noto Sans
Devanagari. That ensures a missing provider font cannot make a text layer
disappear; a future licensed-font pack can add a new document contract version.
