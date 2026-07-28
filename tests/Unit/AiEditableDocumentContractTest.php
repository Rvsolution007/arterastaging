<?php

namespace Tests\Unit;

use App\Services\AiEditableDocumentContract;
use InvalidArgumentException;
use Tests\TestCase;

class AiEditableDocumentContractTest extends TestCase
{
    public function test_it_normalises_a_frame_free_editable_document(): void
    {
        $manifest = $this->validManifest();
        $manifest['layers'][] = [
            'id' => 'headline',
            'name' => 'Headline',
            'type' => 'text',
            'text' => 'Big Festival Sale',
            'z_index' => 4,
            'transform' => ['x' => 80, 'y' => 90, 'width' => 900, 'height' => 140],
        ];

        $normalised = app(AiEditableDocumentContract::class)->validate($manifest);

        $this->assertSame('artera.ai-editable/v1', $normalised['document_contract']);
        $this->assertSame('background', $normalised['layers'][0]['id']);
        $this->assertSame('headline', $normalised['layers'][1]['id']);
        $this->assertFalse($normalised['layers'][0]['locked']);
    }

    public function test_it_refuses_frame_fields_and_render_versions(): void
    {
        $manifest = $this->validManifest();
        $manifest['layers'][0]['_is_frame_layer'] = true;

        $this->expectException(InvalidArgumentException::class);
        app(AiEditableDocumentContract::class)->validate($manifest);
    }

    public function test_v1_rejects_blend_modes_the_flutter_canvas_does_not_compose(): void
    {
        $manifest = $this->validManifest();
        $manifest['layers'][0]['blend_mode'] = 'screen';

        $this->expectException(InvalidArgumentException::class);
        app(AiEditableDocumentContract::class)->validate($manifest);
    }

    public function test_it_preserves_editable_gradient_and_effect_values(): void
    {
        $manifest = $this->validManifest();
        $manifest['layers'][0]['gradient'] = [
            'type' => 'radial',
            'angle' => 120,
            'colors' => ['#F97316', '#0EA5E9'],
        ];
        $manifest['layers'][0]['style'] = ['blur' => 18];

        $normalised = app(AiEditableDocumentContract::class)->validate($manifest);

        $this->assertSame('radial', $normalised['layers'][0]['gradient']['type']);
        $this->assertSame(120, $normalised['layers'][0]['gradient']['angle']);
        $this->assertSame(18, $normalised['layers'][0]['style']['blur']);
    }

    public function test_it_accepts_an_editable_icon_layer(): void
    {
        $manifest = $this->validManifest();
        $manifest['layers'][] = [
            'id' => 'phone_icon',
            'name' => 'Phone icon',
            'type' => 'icon',
            'z_index' => 2,
            'style' => ['icon_name' => 'phone', 'color' => '#FFFFFF'],
            'transform' => ['x' => 80, 'y' => 860, 'width' => 48, 'height' => 48],
        ];

        $normalised = app(AiEditableDocumentContract::class)->validate($manifest);

        $this->assertSame('icon', $normalised['layers'][1]['type']);
        $this->assertFalse($normalised['layers'][1]['locked']);
    }

    private function validManifest(): array
    {
        return [
            'document_contract' => 'artera.ai-editable/v1',
            'schema_version' => 1,
            'canvas' => ['width' => 1080, 'height' => 1080],
            'layers' => [[
                'id' => 'background',
                'name' => 'Background gradient',
                'type' => 'gradient',
                'z_index' => 0,
                'gradient' => ['type' => 'linear', 'colors' => ['#F97316', '#EF4444']],
                'transform' => ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 1080],
            ]],
        ];
    }
}
