<?php

namespace Tests\Unit;

use App\Services\BusinessAiPromptCompiler;
use Tests\TestCase;

class BusinessAiPromptCompilerTest extends TestCase
{
    public function test_it_requests_a_single_artwork_with_editable_safe_zones(): void
    {
        $purpose = ['prompt' => 'Create a clear recruitment campaign visual.'];
        $style = [
            'name' => 'Modern Corporate',
            'description' => 'Clean, premium and confident',
            'prompt_text' => 'Deep navy and indigo premium corporate visual direction.',
        ];

        $prompt = app(BusinessAiPromptCompiler::class)->compile(
            $purpose,
            $style,
            ['job_role' => 'Sales Executive', 'location' => 'Surat, Gujarat'],
            [
                ['title' => 'Product 1', 'image' => 'products/one.png'],
                ['title' => 'Product 2', 'image' => 'products/two.png'],
            ],
            ['name' => 'Artera Pixel'],
            'Keep the subject on the right.',
            '1024x1024',
            [
                'enabled' => true,
                'header_prompt' => 'Reserve a clean logo area at the top.',
                'footer_prompt' => 'Reserve clear contact space at the bottom.',
            ],
        );

        $this->assertStringContainsString('Sales Executive', $prompt);
        $this->assertStringContainsString('Product 1, Product 2', $prompt);
        $this->assertStringContainsString('Do not render any headline', $prompt);
        $this->assertStringContainsString('one cohesive composition', $prompt);
        $this->assertStringContainsString('SELECTED THEME PROMPT', $prompt);
        $this->assertStringContainsString('HEADER/FOOTER STYLE', $prompt);
    }
}
