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

    public function test_it_carries_scoped_general_content_business_facts_and_approved_preview(): void
    {
        $prompt = app(BusinessAiPromptCompiler::class)->compile(
            ['prompt' => 'Create a trustworthy clinical process visual.'],
            ['name' => 'Clean Medical', 'description' => 'Calm', 'prompt_text' => 'Blue clinical composition.'],
            ['service_name' => 'Vision Test'],
            [],
            [
                'name' => 'ClearView Eye Clinic',
                'category' => ['name' => 'Healthcare'],
                'services' => ['Vision Test', 'Eye Consultation'],
            ],
            null,
            '1024x1024',
            [],
            [
                'general_data' => [['text' => 'A check-up can include a vision check and consultation.']],
                'content_instruction' => 'Use simple educational language.',
            ],
            [
                'headline' => 'Complete eye check-up',
                'content' => 'Review your vision with a professional consultation.',
                'cta' => 'Book an appointment',
            ],
        );

        $this->assertStringContainsString('ClearView Eye Clinic', $prompt);
        $this->assertStringContainsString('A check-up can include a vision check', $prompt);
        $this->assertStringContainsString('Use simple educational language', $prompt);
        $this->assertStringContainsString('Complete eye check-up', $prompt);
    }

    public function test_it_uses_the_selected_effective_palette_without_changing_the_style_prompt(): void
    {
        $prompt = app(BusinessAiPromptCompiler::class)->compile(
            ['prompt' => 'Create a premium offer visual.'],
            [
                'name' => 'Modern Clean',
                'prompt_text' => 'Clean modern editorial composition.',
                'palette_mode' => 'business_theme',
                'colors' => ['#4338CA', '#0F172A'],
                'effective_colors' => ['#0066CC', '#F4B400'],
            ],
            [],
            [],
            [],
            null,
            '1024x1024',
        );

        $this->assertStringContainsString('Clean modern editorial composition.', $prompt);
        $this->assertStringContainsString('DESIGN PALETTE (active business theme): primary #0066CC, secondary #F4B400', $prompt);
    }
}
