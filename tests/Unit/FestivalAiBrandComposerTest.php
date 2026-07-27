<?php

namespace Tests\Unit;

use App\Services\FestivalAiBrandComposer;
use Tests\TestCase;

class FestivalAiBrandComposerTest extends TestCase
{
    public function test_disabled_overlay_is_byte_identical(): void
    {
        $source = $this->solidPng();
        $result = app(FestivalAiBrandComposer::class)->compose(
            $source,
            ['name' => 'RV Solutions', 'phones' => ['9876543210']],
            ['overlay_enabled' => false]
        );

        $this->assertSame($source, $result);
    }

    public function test_legacy_callers_cannot_add_a_branding_overlay(): void
    {
        $source = $this->solidPng();
        $result = app(FestivalAiBrandComposer::class)->compose(
            $source,
            ['name' => 'RV Solutions', 'phones' => ['9876543210'], 'emails' => ['hello@example.test']],
            ['header_prompt' => 'legacy header', 'footer_prompt' => 'legacy footer']
        );

        $this->assertSame($source, $result);
    }

    public function test_invalid_image_and_empty_visible_business_are_unchanged(): void
    {
        $composer = app(FestivalAiBrandComposer::class);
        $this->assertSame('not-an-image', $composer->compose('not-an-image', ['name' => 'RV'], []));

        $source = $this->solidPng();
        $this->assertSame($source, $composer->compose($source, [], ['overlay_enabled' => true]));
    }

    private function solidPng(): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required for brand composer tests.');
        }

        $image = imagecreatetruecolor(320, 320);
        $background = imagecolorallocate($image, 238, 232, 216);
        imagefilledrectangle($image, 0, 0, 319, 319, $background);
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return (string) $binary;
    }
}
