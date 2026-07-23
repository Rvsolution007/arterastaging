<?php

namespace App\Services;

use DomainException;

/**
 * The single registry for render-version availability and contract ownership.
 *
 * Versions V11-V25 are deliberately reserved, rather than silently treated
 * as V10.  A future version becomes active only after it has a named contract,
 * explicit parent, capabilities, bidirectional adapter, and tests.
 */
final class FrameRenderContractRegistry
{
    public const MIN_RENDER_VERSION = 1;
    public const CURRENT_RENDER_VERSION = 10;
    public const RESERVED_RENDER_VERSION_MAX = 25;

    /**
     * @var array<int, array{id: string, parent_version: int|null, layer_contract: string, capabilities: array<int, string>}>
     */
    private const ACTIVE_CONTRACTS = [
        1 => ['id' => 'legacy-v1', 'parent_version' => null, 'layer_contract' => 'legacy', 'capabilities' => []],
        2 => ['id' => 'legacy-v2', 'parent_version' => 1, 'layer_contract' => 'legacy', 'capabilities' => []],
        3 => ['id' => 'legacy-v3', 'parent_version' => 2, 'layer_contract' => 'legacy', 'capabilities' => []],
        4 => ['id' => 'legacy-v4', 'parent_version' => 3, 'layer_contract' => 'legacy', 'capabilities' => []],
        5 => ['id' => 'legacy-v5', 'parent_version' => 4, 'layer_contract' => 'legacy', 'capabilities' => []],
        6 => ['id' => 'legacy-v6', 'parent_version' => 5, 'layer_contract' => 'legacy', 'capabilities' => []],
        7 => ['id' => 'legacy-v7', 'parent_version' => 6, 'layer_contract' => 'legacy', 'capabilities' => []],
        8 => ['id' => 'legacy-v8', 'parent_version' => 7, 'layer_contract' => 'legacy', 'capabilities' => []],
        9 => ['id' => 'legacy-v9', 'parent_version' => 8, 'layer_contract' => 'legacy', 'capabilities' => []],
        10 => [
            'id' => 'native-vector-v10',
            'parent_version' => 9,
            'layer_contract' => 'native_vector',
            'capabilities' => [
                'stable_layer_ids',
                'canonical_bounds_and_z_index',
                'dynamic_contrast_colours',
                'icon_source_metadata',
                'independent_corner_radii',
            ],
        ],
    ];

    /** @return array<int, array{id: string, parent_version: int|null, layer_contract: string, capabilities: array<int, string>}> */
    public function activeContracts(): array
    {
        return self::ACTIVE_CONTRACTS;
    }

    /** @return array<int, int> */
    public function activeVersions(): array
    {
        return array_keys(self::ACTIVE_CONTRACTS);
    }

    /** @return array{version: int, id: string, parent_version: int|null, layer_contract: string, capabilities: array<int, string>, status: string} */
    public function profile(int $version): array
    {
        $this->assertRegistered($version);
        $contract = self::ACTIVE_CONTRACTS[$version];

        return [
            'version' => $version,
            'id' => $contract['id'],
            'parent_version' => $contract['parent_version'],
            'layer_contract' => $contract['layer_contract'],
            'capabilities' => $contract['capabilities'],
            'status' => 'active',
        ];
    }

    /**
     * Gives planning tooling and future agents an explicit answer without
     * accidentally allowing a migration to an unimplemented version.
     *
     * @return array{version: int, status: string, parent_version: null, required: array<int, string>}
     */
    public function reservedProfile(int $version): array
    {
        if ($version <= self::CURRENT_RENDER_VERSION || $version > self::RESERVED_RENDER_VERSION_MAX) {
            throw new DomainException("V{$version} is not a reserved future render version.");
        }

        return [
            'version' => $version,
            'status' => 'reserved',
            'parent_version' => null,
            'required' => [
                'contract_id',
                'parent_version',
                'capabilities',
                'upgrade_adapter',
                'lossless_downgrade_adapter',
                'round_trip_tests',
                'web_native_render_fixtures',
            ],
        ];
    }

    public function isRegistered(int $version): bool
    {
        return array_key_exists($version, self::ACTIVE_CONTRACTS);
    }

    public function assertRegistered(int $version): void
    {
        if ($this->isRegistered($version)) {
            return;
        }

        if ($version > self::CURRENT_RENDER_VERSION && $version <= self::RESERVED_RENDER_VERSION_MAX) {
            throw new DomainException(
                "V{$version} is reserved but not active. Register its contract, parent, bidirectional migration adapters, and tests before enabling it.",
            );
        }

        throw new DomainException("Render version V{$version} is not registered for migration.");
    }

    public function contractForVersion(int $version): string
    {
        return $this->profile($version)['id'];
    }

    public function usesNativeVectorContract(int $version): bool
    {
        // A future V11+ contract can keep the native layer family while using
        // its own contract id. Never couple inheritance to the literal V10 id.
        return $this->profile($version)['layer_contract'] === 'native_vector';
    }

    /** @return array<int, int> Versions entered while travelling from source to target. */
    public function migrationPath(int $fromVersion, int $targetVersion): array
    {
        $this->assertRegistered($fromVersion);
        $this->assertRegistered($targetVersion);

        if ($fromVersion === $targetVersion) {
            return [$targetVersion];
        }

        $step = $targetVersion > $fromVersion ? 1 : -1;
        $path = [];
        for ($version = $fromVersion + $step; ; $version += $step) {
            $path[] = $version;
            if ($version === $targetVersion) {
                return $path;
            }
        }
    }
}
