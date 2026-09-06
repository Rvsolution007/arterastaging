<?php

namespace Tests\Unit;

use App\Services\AiEditableDocumentContract;
use InvalidArgumentException;
use Tests\TestCase;

class AiEditableDocumentContractTest extends TestCase
{
    public function test_v2_accepts_only_locked_artwork_and_editable_text(): void
    {
        $manifest = app(AiEditableDocumentContract::class)->validate([
            'document_contract' => 'artera.ai-editable/v2',
            'schema_version' => 2,
            'canvas' => ['width' => 1080, 'height' => 1080],
            'layers' => [
                [
                    'id' => 'ai_artwork', 'name' => 'AI artwork', 'type' => 'bitmap',
                    'locked' => true, 'asset' => ['path' => 'business_ai/example.png'],
                    'transform' => ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 1080],
                ],
                [
                    'id' => 'text_1', 'name' => 'Headline', 'type' => 'text', 'text' => 'New offer',
                    'locked' => false,
                    'transform' => ['x' => 50, 'y' => 50, 'width' => 800, 'height' => 150],
                ],
            ],
        ]);

        $this->assertSame('artera.ai-editable/v2', $manifest['document_contract']);
        $this->assertTrue($manifest['layers'][0]['locked']);
        $this->assertFalse($manifest['layers'][1]['locked']);
    }

    public function test_v2_rejects_an_editable_non_text_layer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AiEditableDocumentContract::class)->validate([
            'document_contract' => 'artera.ai-editable/v2',
            'schema_version' => 2,
            'canvas' => ['width' => 1080, 'height' => 1080],
            'layers' => [[
                'id' => 'ai_artwork', 'name' => 'AI artwork', 'type' => 'bitmap',
                'locked' => false, 'asset' => ['path' => 'business_ai/example.png'],
                'transform' => ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 1080],
            ]],
        ]);
    }
}
