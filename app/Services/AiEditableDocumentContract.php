<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * V1 contract validator for the isolated AI editor.  It intentionally uses
 * normalised top-left bounds (x/y/width/height) and has no dependency on the
 * frame render_version contract.
 */
class AiEditableDocumentContract
{
    public function validate(array $manifest): array
    {
        $contract = $manifest['document_contract'] ?? null;
        $contracts = (array) config('ai_editable_v1.contracts', []);
        $definition = (array) ($contracts[$contract] ?? []);
        if ($definition === []) {
            throw new InvalidArgumentException('Unsupported AI editable document contract.');
        }

        $schemaVersion = $this->integer($manifest['schema_version'] ?? null, 'schema_version');
        if ($schemaVersion !== (int) ($definition['schema_version'] ?? 0)) {
            throw new InvalidArgumentException('Unsupported AI editable schema version.');
        }

        if (array_key_exists('render_version', $manifest)) {
            throw new InvalidArgumentException('AI editable documents cannot use the frame render_version contract.');
        }

        $canvas = $manifest['canvas'] ?? null;
        if (!is_array($canvas)) {
            throw new InvalidArgumentException('Document canvas is required.');
        }

        $maxDimension = (int) config('ai_editable_v1.max_canvas_dimension', 8192);
        $width = $this->integer($canvas['width'] ?? null, 'canvas.width');
        $height = $this->integer($canvas['height'] ?? null, 'canvas.height');
        if ($width < 64 || $width > $maxDimension || $height < 64 || $height > $maxDimension) {
            throw new InvalidArgumentException('Canvas dimensions are outside the supported AI editor range.');
        }

        $layers = $manifest['layers'] ?? null;
        if (!is_array($layers) || $layers === []) {
            throw new InvalidArgumentException('At least one editable layer is required.');
        }
        if (count($layers) > (int) config('ai_editable_v1.max_layers', 32)) {
            throw new InvalidArgumentException('This AI editable document has too many layers.');
        }

        $seenIds = [];
        $normalisedLayers = [];
        foreach ($layers as $index => $layer) {
            if (!is_array($layer)) {
                throw new InvalidArgumentException("Layer {$index} is invalid.");
            }
            $normalisedLayers[] = $this->validateLayer($layer, $width, $height, $seenIds, $index, $definition);
        }

        usort($normalisedLayers, static fn (array $left, array $right) => $left['z_index'] <=> $right['z_index']);

        $normalised = $manifest;
        $normalised['document_contract'] = $contract;
        $normalised['schema_version'] = $schemaVersion;
        $normalised['canvas'] = array_merge($canvas, ['width' => $width, 'height' => $height]);
        $normalised['layers'] = $normalisedLayers;

        return $normalised;
    }

    public function checksum(array $manifest): string
    {
        return hash('sha256', json_encode($this->sortRecursively($manifest), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function validateLayer(array $layer, int $canvasWidth, int $canvasHeight, array &$seenIds, int $index, array $definition): array
    {
        foreach (['_is_frame_layer', '_isFrameLayer', 'frame_id', 'render_version'] as $forbiddenKey) {
            if (array_key_exists($forbiddenKey, $layer)) {
                throw new InvalidArgumentException("Layer {$index} contains a frame-only field.");
            }
        }

        $id = trim((string) ($layer['id'] ?? ''));
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $id) || isset($seenIds[$id])) {
            throw new InvalidArgumentException("Layer {$index} needs a unique stable id.");
        }
        $seenIds[$id] = true;

        $type = (string) ($layer['type'] ?? '');
        if (!in_array($type, (array) ($definition['layer_types'] ?? []), true)) {
            throw new InvalidArgumentException("Layer {$id} has an unsupported type.");
        }

        $name = trim((string) ($layer['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException("Layer {$id} needs a name.");
        }

        $transform = $layer['transform'] ?? null;
        if (!is_array($transform)) {
            throw new InvalidArgumentException("Layer {$id} needs normalised bounds.");
        }
        $x = $this->number($transform['x'] ?? null, "layer {$id}.transform.x");
        $y = $this->number($transform['y'] ?? null, "layer {$id}.transform.y");
        $width = $this->number($transform['width'] ?? null, "layer {$id}.transform.width");
        $height = $this->number($transform['height'] ?? null, "layer {$id}.transform.height");
        $rotation = $this->number($transform['rotation'] ?? 0, "layer {$id}.transform.rotation");
        if ($width <= 0 || $height <= 0 || $width > $canvasWidth * 4 || $height > $canvasHeight * 4) {
            throw new InvalidArgumentException("Layer {$id} has invalid bounds.");
        }

        $opacity = $this->number($layer['opacity'] ?? 1, "layer {$id}.opacity");
        if ($opacity < 0 || $opacity > 1) {
            throw new InvalidArgumentException("Layer {$id} opacity must be between 0 and 1.");
        }
        $blendMode = (string) ($layer['blend_mode'] ?? 'normal');
        if (!in_array($blendMode, (array) config('ai_editable_v1.blend_modes'), true)) {
            throw new InvalidArgumentException("Layer {$id} has an unsupported blend mode.");
        }

        if ($type === 'text' && trim((string) ($layer['text'] ?? '')) === '') {
            throw new InvalidArgumentException("Text layer {$id} cannot be empty.");
        }
        if ($type === 'bitmap' && !is_array($layer['asset'] ?? null)) {
            throw new InvalidArgumentException("Bitmap layer {$id} needs an image asset.");
        }
        if ($type === 'gradient' && !is_array($layer['gradient'] ?? null)) {
            throw new InvalidArgumentException("Gradient layer {$id} needs gradient data.");
        }

        $normalised = $layer;
        $normalised['id'] = $id;
        $normalised['type'] = $type;
        $normalised['name'] = $name;
        $normalised['z_index'] = $this->integer($layer['z_index'] ?? $index, "layer {$id}.z_index");
        $normalised['opacity'] = $opacity;
        $normalised['blend_mode'] = $blendMode;
        $normalised['visible'] = !array_key_exists('visible', $layer) || (bool) $layer['visible'];
        $normalised['locked'] = (bool) ($layer['locked'] ?? false);
        $normalised['transform'] = array_merge($transform, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'rotation' => $rotation,
        ]);

        if (!empty($definition['text_only'])) {
            if ($type === 'text' && $normalised['locked']) {
                throw new InvalidArgumentException("Text layer {$id} must remain editable in this document contract.");
            }
            if ($type !== 'text' && !$normalised['locked']) {
                throw new InvalidArgumentException("Only text layers may be editable in this document contract.");
            }
        }

        return $normalised;
    }

    private function integer(mixed $value, string $field): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("{$field} must be an integer.");
        }

        return (int) $value;
    }

    private function number(mixed $value, string $field): float
    {
        if (!is_numeric($value) || !is_finite((float) $value)) {
            throw new InvalidArgumentException("{$field} must be a finite number.");
        }

        return (float) $value;
    }

    private function sortRecursively(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!$this->isList($value)) {
            ksort($value);
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursively($item);
        }

        return $value;
    }

    private function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }
}
