<?php

namespace Tests\Unit;

use App\Services\FrameContractMigrator;
use App\Services\FrameRenderContractRegistry;
use DomainException;
use Tests\TestCase;

class FrameContractMigratorTest extends TestCase
{
    public function test_upgrade_to_native_contract_preserves_authored_colours_and_makes_runtime_data_explicit(): void
    {
        $migrator = new FrameContractMigrator();
        $result = $migrator->migrate([
            'render_version' => 9,
            'layers' => [
                [
                    'name' => 'headline',
                    'type' => 'text',
                    'x' => 20,
                    'y' => 30,
                    'w' => 300,
                    'h' => 80,
                    'z_index' => '4',
                    'color' => '#123A9E',
                    '_resolved_color' => '#FFFFFF',
                ],
                [
                    'name' => 'phone_icon',
                    'type' => 'image',
                    '_originalType' => 'icon',
                    'w' => 24,
                    'h' => 24,
                    'tint_color' => '#FFF2B2',
                ],
            ],
        ], 9, 10);

        $json = $result['json'];
        $this->assertSame(10, $json['render_version']);
        $this->assertSame('native-vector-v10', $json['render_contract']);
        $this->assertSame([10], $result['migration_path']);

        $text = $json['layers'][0];
        $this->assertSame('#123A9E', $text['original_color']);
        $this->assertSame('#123A9E', $text['color']);
        $this->assertSame('dynamic-contrast-v10', $text['_color_contract']);
        $this->assertArrayNotHasKey('_resolved_color', $text);
        $this->assertSame('v10_migrated_0_headline', $text['id']);
        $this->assertSame(4, $text['z_index']);
        $this->assertSame(300, $text['width']);

        $icon = $json['layers'][1];
        $this->assertSame('image', $icon['type']);
        $this->assertSame('icon', $icon['_originalType']);
        $this->assertSame('#FFF2B2', $icon['original_color']);
        $this->assertSame('#FFF2B2', $icon['tint_color']);
    }

    public function test_downgrade_restores_legacy_colour_fields_without_overwriting_authored_colour(): void
    {
        $migrator = new FrameContractMigrator();
        $result = $migrator->migrate([
            'render_version' => 10,
            'layers' => [[
                'id' => 'v10_headline',
                'name' => 'headline',
                'type' => 'text',
                'original_color' => '#123A9E',
                '_resolved_color' => '#FFFFFF',
            ]],
        ], 10, 9);

        $layer = $result['json']['layers'][0];
        $this->assertSame(9, $result['json']['render_version']);
        $this->assertSame('legacy-v9', $result['json']['render_contract']);
        $this->assertSame('#123A9E', $layer['color']);
        $this->assertSame('#123A9E', $layer['font_color']);
        $this->assertArrayNotHasKey('_resolved_color', $layer);
        $this->assertArrayNotHasKey('_color_contract', $layer);
        $this->assertSame([9], $result['migration_path']);
    }

    public function test_downgrade_refuses_a_v10_only_shape_instead_of_losing_data(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('independent corner radii');

        (new FrameContractMigrator())->migrate([
            'layers' => [[
                'name' => 'card',
                'type' => 'shape',
                'corner_radii' => [
                    'top_left' => 8,
                    'top_right' => 16,
                    'bottom_right' => 8,
                    'bottom_left' => 8,
                ],
            ]],
        ], 10, 9);
    }

    public function test_future_versions_are_reserved_until_their_contract_and_adapters_exist(): void
    {
        $registry = new FrameRenderContractRegistry();
        $reserved = $registry->reservedProfile(11);

        $this->assertTrue($registry->usesNativeVectorContract(10));
        $this->assertSame('reserved', $reserved['status']);
        $this->assertContains('lossless_downgrade_adapter', $reserved['required']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('V11 is reserved but not active');
        (new FrameContractMigrator($registry))->migrate(['layers' => []], 10, 11);
    }

    public function test_forward_compatible_extensions_survive_a_legacy_round_trip(): void
    {
        $migrator = new FrameContractMigrator();
        $payload = [
            'layers' => [[
                'id' => 'v10_headline',
                'name' => 'headline',
                'type' => 'text',
                'original_color' => '#123A9E',
            ]],
            '_version_extensions' => [
                'v11' => ['future_feature' => ['value' => 'preserve exactly']],
            ],
        ];

        $downgraded = $migrator->migrate($payload, 10, 9)['json'];
        $upgraded = $migrator->migrate($downgraded, 9, 10)['json'];

        $this->assertSame($payload['_version_extensions'], $upgraded['_version_extensions']);
        $this->assertSame('#123A9E', $upgraded['layers'][0]['original_color']);
    }
}
