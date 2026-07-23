<?php

namespace App\Services;

use DomainException;

/**
 * Converts a complete frame payload between renderer contracts.
 *
 * `render_version` is an executable data contract, not merely a renderer
 * switch.  V1–V9 retain their legacy contract. V10 and later inherit the
 * native/vector contract until a later version registers a new contract here.
 * Keeping this logic pure makes migrations testable before any ZIP, database,
 * or cache source is written.
 */
class FrameContractMigrator
{
    /** @deprecated Use FrameRenderContractRegistry::CURRENT_RENDER_VERSION. */
    public const CURRENT_RENDER_VERSION = FrameRenderContractRegistry::CURRENT_RENDER_VERSION;

    private FrameRenderContractRegistry $contracts;

    public function __construct(?FrameRenderContractRegistry $contracts = null)
    {
        $this->contracts = $contracts ?? new FrameRenderContractRegistry();
    }

    public function migrate(array $payload, int $fromVersion, int $targetVersion): array
    {
        $this->contracts->assertRegistered($fromVersion);
        $this->contracts->assertRegistered($targetVersion);

        // Do not mutate the value read from the source of truth. A caller can
        // validate this candidate and persist it atomically only on success.
        $json = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $changes = [];
        $layerKey = $this->layerCollectionKey($json);

        if ($layerKey !== null) {
            if ($targetVersion < 10) {
                $this->assertLegacyCompatibility($json[$layerKey], $targetVersion);
            }

            // Transitions are intentionally walked one registered contract at
            // a time. V11+ adapters plug into this route; a version can never
            // become a number-only shortcut around its upgrade/downgrade work.
            foreach ($this->contracts->migrationPath($fromVersion, $targetVersion) as $contractVersion) {
                foreach ($json[$layerKey] as $index => &$layer) {
                    if (!is_array($layer)) {
                        continue;
                    }

                    if ($this->contracts->usesNativeVectorContract($contractVersion)) {
                        $this->migrateLayerToNativeContract($layer, $index, $changes);
                    } else {
                        $this->migrateLayerToLegacyContract($layer, $changes);
                    }
                }
                unset($layer);
            }
        }

        $json['render_version'] = $targetVersion;
        $json['render_contract'] = $this->contractForVersion($targetVersion);
        unset($json['_resolved_color']);

        return [
            'json' => $json,
            'changes' => array_values(array_unique($changes)),
            'from_contract' => $this->contractForVersion($fromVersion),
            'to_contract' => $this->contractForVersion($targetVersion),
            'migration_path' => $this->contracts->migrationPath($fromVersion, $targetVersion),
        ];
    }

    public function contractForVersion(int $version): string
    {
        return $this->contracts->contractForVersion($version);
    }

    private function layerCollectionKey(array $payload): ?string
    {
        foreach (['layers', 'objects', 'elements'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * A downgrade must be lossless. If the target contract cannot represent a
     * feature exactly, refuse the migration rather than accepting an unsafe
     * "force commit" and silently changing the artwork.
     */
    private function assertLegacyCompatibility(array $layers, int $targetVersion): void
    {
        $names = [];
        foreach ($layers as $index => $layer) {
            if (!is_array($layer)) {
                continue;
            }

            $name = trim((string) ($layer['name'] ?? ''));
            if ($name !== '') {
                if (isset($names[$name])) {
                    throw new DomainException(
                        "Cannot downgrade to V{$targetVersion}: layers #{$names[$name]} and #{$index} share the name \"{$name}\". " .
                        'V10+ can identify duplicate names by id; legacy versions cannot.',
                    );
                }
                $names[$name] = $index;
            }

            $radii = $layer['corner_radii'] ?? $layer['cornerRadii'] ?? null;
            if (!is_array($radii)) {
                continue;
            }

            $values = [];
            foreach (['top_left', 'top_right', 'bottom_right', 'bottom_left', 'topLeft', 'topRight', 'bottomRight', 'bottomLeft'] as $key) {
                if (array_key_exists($key, $radii)) {
                    $values[] = (float) $radii[$key];
                }
            }
            if (count(array_unique($values, SORT_REGULAR)) > 1) {
                $layerName = $name !== '' ? $name : "#{$index}";
                throw new DomainException(
                    "Cannot downgrade layer {$layerName} to V{$targetVersion}: independent corner radii are a V10+ feature.",
                );
            }
        }
    }

    private function migrateLayerToNativeContract(array &$layer, int $index, array &$changes): void
    {
        unset($layer['_resolved_color']);

        if (empty(trim((string) ($layer['id'] ?? '')))) {
            $layer['id'] = $this->stableNativeLayerId($layer, $index);
            $changes[] = 'stable_layer_ids';
        }

        $this->normaliseBoundsAndZIndex($layer, $index, $changes);

        $isText = ($layer['type'] ?? null) === 'text';
        $isIcon = $this->isIconLayer($layer);
        if (!$isText && !$isIcon) {
            return;
        }

        $original = $this->canonicalColor($layer, $isIcon);
        if ($original === null) {
            return;
        }

        $layer['original_color'] = $original;
        $layer['_color_contract'] = 'dynamic-contrast-v10';
        $changes[] = 'dynamic_colour_contract';

        if ($isIcon) {
            // Existing raster icons stay image-backed. Their identity is
            // explicit, so native V10+ can resolve colour without pretending
            // that a PNG is a vector SVG.
            if (($layer['type'] ?? null) === 'image') {
                $layer['_originalType'] = 'icon';
            }
            $layer['color'] = $original;
            $layer['tint_color'] = $original;
            $layer['font_color'] = $original;
        }

        if ($isText) {
            $layer['color'] = $original;
            $layer['font_color'] = $original;
        }

        if (isset($layer['_source_meta']) && is_array($layer['_source_meta'])) {
            $layer['_source_meta']['original_color'] = $original;
        }
    }

    private function migrateLayerToLegacyContract(array &$layer, array &$changes): void
    {
        unset($layer['_resolved_color'], $layer['_color_contract']);

        $isText = ($layer['type'] ?? null) === 'text';
        $isIcon = $this->isIconLayer($layer);
        if (!$isText && !$isIcon) {
            return;
        }

        $original = $this->canonicalColor($layer, $isIcon);
        if ($original === null) {
            return;
        }

        // Legacy renderers consume these fields directly. This is a target
        // contract conversion, not a runtime colour resolution; the authored
        // original remains available for a future upgrade.
        $layer['color'] = $original;
        $layer['font_color'] = $original;
        if ($isIcon) {
            $layer['tint_color'] = $original;
        }
        $changes[] = 'legacy_colour_fields';
    }

    private function normaliseBoundsAndZIndex(array &$layer, int $index, array &$changes): void
    {
        foreach ([['w', 'width'], ['h', 'height']] as [$short, $long]) {
            if (!isset($layer[$short]) && isset($layer[$long])) {
                $layer[$short] = $layer[$long];
                $changes[] = 'canonical_bounds';
            }
            if (!isset($layer[$long]) && isset($layer[$short])) {
                $layer[$long] = $layer[$short];
                $changes[] = 'canonical_bounds';
            }
        }

        if (isset($layer['z_index']) && is_numeric($layer['z_index'])) {
            $layer['z_index'] = (int) $layer['z_index'];
        } elseif (!isset($layer['z_index'])) {
            $layer['z_index'] = $index + 1;
            $changes[] = 'canonical_z_index';
        }
    }

    private function stableNativeLayerId(array $layer, int $index): string
    {
        $name = strtolower((string) ($layer['name'] ?? 'layer'));
        $name = preg_replace('/[^a-z0-9]+/', '_', $name) ?: 'layer';
        $name = trim($name, '_') ?: 'layer';

        return "v10_migrated_{$index}_{$name}";
    }

    private function isIconLayer(array $layer): bool
    {
        if (($layer['type'] ?? null) === 'icon' || ($layer['_originalType'] ?? null) === 'icon') {
            return true;
        }

        if (isset($layer['_source_meta'])
            && is_array($layer['_source_meta'])
            && ($layer['_source_meta']['type'] ?? null) === 'icon') {
            return true;
        }

        if (($layer['type'] ?? null) !== 'image') {
            return false;
        }

        if (in_array((string) ($layer['_businessKey'] ?? ''), [
            'phone', 'email', 'website', 'address', 'social',
        ], true)) {
            return true;
        }

        $name = strtolower((string) ($layer['name'] ?? $layer['id'] ?? ''));
        foreach ([
            'icon', 'phone', 'email', 'website', 'address', 'call', 'mobile',
            'contact', 'whatsapp', 'tel', 'mail', 'web', 'url', 'location',
            'facebook', 'instagram', 'twitter', 'youtube', 'linkedin',
        ] as $needle) {
            if (str_contains($name, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function canonicalColor(array $layer, bool $isIcon): ?string
    {
        $sourceMeta = isset($layer['_source_meta']) && is_array($layer['_source_meta'])
            ? $layer['_source_meta']
            : [];
        $candidates = $isIcon
            ? [
                $layer['original_color'] ?? null,
                $sourceMeta['original_color'] ?? null,
                $sourceMeta['originalColor'] ?? null,
                $layer['tint_color'] ?? null,
                $layer['color'] ?? null,
                $layer['font_color'] ?? null,
            ]
            : [
                $layer['original_color'] ?? null,
                $sourceMeta['original_color'] ?? null,
                $sourceMeta['originalColor'] ?? null,
                $layer['color'] ?? null,
                $layer['font_color'] ?? null,
                $layer['fill'] ?? null,
                isset($layer['font']) && is_array($layer['font']) ? ($layer['font']['color'] ?? null) : null,
            ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }
}
