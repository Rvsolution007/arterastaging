<?php

namespace Tests\Unit;

use App\Services\FestivalAiBrandComposer;
use App\Services\FestivalAiImageService;
use ReflectionMethod;
use Tests\TestCase;

class FestivalAiReferenceResolverTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    public function test_festival_ai_uploads_path_is_not_corrupted_and_non_images_are_rejected(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required for image reference tests.');
        }

        $directory = public_path('uploads/festival_ai_uploads');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $validPath = $directory . DIRECTORY_SEPARATOR . 'codex-reference-test.png';
        $invalidPath = $directory . DIRECTORY_SEPARATOR . 'codex-reference-invalid.png';
        file_put_contents($validPath, $this->solidPng());
        file_put_contents($invalidPath, '<html>not an image</html>');
        $this->temporaryFiles = [$validPath, $invalidPath];

        $service = new FestivalAiImageService(app(FestivalAiBrandComposer::class));
        $method = new ReflectionMethod($service, 'imageContents');
        $method->setAccessible(true);

        $this->assertIsString($method->invoke($service, 'festival_ai_uploads/codex-reference-test.png'));
        $this->assertNull($method->invoke($service, 'festival_ai_uploads/codex-reference-invalid.png'));
        $this->assertNull($method->invoke($service, '../.env'));
    }

    private function solidPng(): string
    {
        $image = imagecreatetruecolor(20, 20);
        $background = imagecolorallocate($image, 55, 95, 145);
        imagefilledrectangle($image, 0, 0, 19, 19, $background);
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return (string) $binary;
    }
}
