<?php

namespace Database\Seeders;

use App\Models\BusinessAiPurpose;
use App\Models\BusinessAiPurposeScope;
use App\Models\BusinessAiStyle;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HealthcareAiPostDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $category = $this->category('Healthcare');

            $subCategories = [
                'Clinic' => $this->subCategory($category, 'Clinic'),
                'Skin Clinic' => $this->subCategory($category, 'Skin Clinic'),
                'Wellness Center' => $this->subCategory($category, 'Wellness Center'),
            ];

            $purposes = [
                'Promotion / Offer' => $this->purpose('Promotion / Offer'),
                'Announcement / Event' => $this->purpose('Announcement / Event'),
                'Service / Awareness' => $this->purpose('Service / Awareness'),
            ];

            $styles = [
                'clinical' => $this->style($purposes['Service / Awareness'], [
                    'key' => 'clinical_aesthetic_luxury',
                    'name' => 'Clinical Aesthetic Luxury',
                    'description' => 'Warm ivory and wine medical-aesthetic education',
                    'prompt_text' => 'Premium medical-aesthetic art direction with warm ivory, muted wine and soft blush accents; refined editorial hierarchy; generous whitespace; understated anatomical or treatment-supporting imagery; clean premium lighting. Do not render logos, contact details, icons or business text.',
                    'colors' => ['#F4EBDD', '#804B4A', '#D9A8A2'],
                    'sort_order' => 20,
                ]),
                'offer' => $this->style($purposes['Promotion / Offer'], [
                    'key' => 'aesthetic_offer_luxe',
                    'name' => 'Aesthetic Offer Luxe',
                    'description' => 'Premium service offer with clear price hierarchy',
                    'prompt_text' => 'Premium healthcare offer art direction using rich red, champagne and ivory; decisive price or discount safe zone; elegant service-relevant imagery; restrained sparkle or gradient accents; clear hierarchy and generous whitespace. Do not render logos, contact details, icons or business text.',
                    'colors' => ['#C62822', '#F0D6B0', '#FFF7ED'],
                    'sort_order' => 21,
                ]),
                'occasion' => $this->style($purposes['Announcement / Event'], [
                    'key' => 'wellness_occasion_warm',
                    'name' => 'Wellness Occasion Warm',
                    'description' => 'Warm, elegant wellbeing occasions and announcements',
                    'prompt_text' => 'Warm premium wellbeing occasion art direction with ivory, champagne and muted wine; optimistic adult lifestyle imagery; subtle seasonal motif; elegant editorial hierarchy; calm uncluttered composition. Do not render logos, contact details, icons or business text.',
                    'colors' => ['#F4EBDD', '#B68B6A', '#804B4A'],
                    'sort_order' => 22,
                ]),
            ];

            $purposes['Service / Awareness']->styles()->syncWithoutDetaching([$styles['clinical']->id]);
            $purposes['Promotion / Offer']->styles()->syncWithoutDetaching([$styles['offer']->id]);
            $purposes['Announcement / Event']->styles()->syncWithoutDetaching([$styles['occasion']->id]);

            $serviceBrief = [
                ['key' => 'service_name', 'label' => 'Service or topic', 'hint' => 'Laser care, body sculpting or hair therapy', 'required' => true],
                ['key' => 'primary_message', 'label' => 'Primary message', 'hint' => 'Explain one concern, benefit or awareness point', 'required' => true],
                ['key' => 'call_to_action', 'label' => 'Call to action', 'hint' => 'Book a consultation', 'required' => false],
            ];
            $offerBrief = [
                ['key' => 'offer_title', 'label' => 'Offer title', 'hint' => 'Laser facial package', 'required' => true],
                ['key' => 'offer_detail', 'label' => 'Offer detail', 'hint' => '50% off or package price', 'required' => true],
                ['key' => 'valid_until', 'label' => 'Valid until', 'hint' => '31 August', 'required' => false],
                ['key' => 'call_to_action', 'label' => 'Call to action', 'hint' => 'Book now', 'required' => false],
            ];
            $announcementBrief = [
                ['key' => 'occasion_or_event', 'label' => 'Occasion or event', 'hint' => "Women's Day or New Year", 'required' => true],
                ['key' => 'message', 'label' => 'Message', 'hint' => 'A short wellbeing message', 'required' => true],
            ];

            $this->saveScope($category, $subCategories['Wellness Center'], $purposes['Service / Awareness'], $styles['clinical'], [
                'Weight-management communication should focus on healthy, individualized support rather than a one-size-fits-all promise.',
                'Body-contouring and fat-reduction posts can explain non-surgical options, assessment and realistic next steps.',
                'Metabolism, sleep, stress, hormones and lifestyle can be presented as factors for awareness, with clinical consultation encouraged when appropriate.',
                'Use inclusive adult representation and avoid body-shaming language or guaranteed outcomes.',
            ], 'Use plain, supportive language. Explain one concern or service at a time, avoid guaranteed results, and end with a consultation-oriented call to action.', $serviceBrief);

            $this->saveScope($category, $subCategories['Wellness Center'], $purposes['Promotion / Offer'], $styles['offer'], [
                'Wellness offers may cover consultation-led weight management, body contouring or fat-reduction services.',
                'Show the service, offer detail and validity clearly; confirm price, duration, inclusions and exclusions before publishing.',
                'Offer visuals must remain premium, respectful and non-body-shaming.',
            ], 'Lead with the service and verified offer detail. Keep eligibility and validity clear, avoid outcome guarantees, and use a direct booking call to action.', $offerBrief);

            $this->saveScope($category, $subCategories['Skin Clinic'], $purposes['Service / Awareness'], $styles['clinical'], [
                'Skin-clinic awareness can cover laser care, facials, pigmentation, skin firmness, thread lift and other aesthetic-care topics.',
                'Explain concerns and care pathways clearly without diagnosing from an image or promising a specific result.',
                'Use clean clinical-luxury visuals with a respectful, confidence-focused tone.',
            ], 'Use precise but easy-to-understand skincare language. Do not guarantee results, diagnose conditions or overstate treatment claims; invite a professional consultation.', $serviceBrief);

            $this->saveScope($category, $subCategories['Skin Clinic'], $purposes['Promotion / Offer'], $styles['offer'], [
                'Skin-clinic offers may promote verified facial, laser hair removal, skin polishing or aesthetic-care packages.',
                'Present sessions, price, savings and eligibility only when those details are supplied and verified.',
                'Keep the creative premium and clinic-led rather than retail-sale driven.',
            ], 'Make the service and verified offer detail the visual priority. Use a concise booking CTA and do not claim guaranteed cosmetic or medical outcomes.', $offerBrief);

            $this->saveScope($category, $subCategories['Clinic'], $purposes['Service / Awareness'], $styles['clinical'], [
                'General clinic awareness can cover consultation-led care, hair-growth support, scalp therapy and mixed clinic services.',
                'Hair and wellness communication should explain the concern, assessment and available support without promising regrowth or cure.',
                'Use medically responsible, confidence-focused language suitable for adult audiences.',
            ], 'Keep the post clinically responsible and easy to understand. Do not use cure, permanent-result or guaranteed-regrowth claims; use a consultation CTA.', $serviceBrief);

            $this->saveScope($category, $subCategories['Clinic'], $purposes['Promotion / Offer'], $styles['offer'], [
                'General clinic offers may combine verified skin, hair, body-care or consultation packages when a single specialty does not apply.',
                'Every offer must state its service, price or discount, validity and exclusions accurately before publishing.',
                'Use a calm premium visual treatment and a direct booking call to action.',
            ], 'Use only verified promotional details. Keep copy concise, avoid guarantees and include a clear booking call to action.', $offerBrief);

            $this->saveScope($category, $subCategories['Clinic'], $purposes['Announcement / Event'], $styles['occasion'], [
                'Clinic announcements may cover wellbeing observances, seasonal greetings, awareness days and verified clinic events.',
                'Use a warm, inclusive message that connects confidence and wellbeing without turning every event into an offer.',
                'Include event date or venue only when supplied and verified.',
            ], 'Lead with the occasion or announcement. Keep the message warm and brief, avoid unsupported health claims, and include logistical details only when verified.', $announcementBrief);
        });
    }

    private function category(string $name): BusinessCategory
    {
        return BusinessCategory::query()->where('name', $name)->first()
            ?? throw new RuntimeException("Required business category [{$name}] does not exist.");
    }

    private function subCategory(BusinessCategory $category, string $name): BusinessSubCategory
    {
        return BusinessSubCategory::query()
            ->where('business_category_id', $category->id)
            ->where('name', $name)
            ->first()
            ?? throw new RuntimeException("Required subcategory [{$category->name} → {$name}] does not exist.");
    }

    private function purpose(string $title): BusinessAiPurpose
    {
        return BusinessAiPurpose::query()->where('title', $title)->first()
            ?? throw new RuntimeException("Required Custom Post Type [{$title}] does not exist.");
    }

    private function style(BusinessAiPurpose $owner, array $attributes): BusinessAiStyle
    {
        return BusinessAiStyle::query()->updateOrCreate(
            ['business_ai_purpose_id' => $owner->id, 'key' => $attributes['key']],
            $attributes + ['status' => true],
        );
    }

    private function saveScope(
        BusinessCategory $category,
        BusinessSubCategory $subCategory,
        BusinessAiPurpose $purpose,
        BusinessAiStyle $style,
        array $generalData,
        string $contentInstruction,
        array $briefFields,
    ): void {
        $scope = BusinessAiPurposeScope::query()->updateOrCreate(
            [
                'business_ai_purpose_id' => $purpose->id,
                'business_category_id' => $category->id,
                'business_sub_category_id' => $subCategory->id,
            ],
            [
                'brief_fields' => $briefFields,
                'brief_fields_source_scope_id' => null,
                'general_data' => $generalData,
                'content_instruction' => $contentInstruction,
                'status' => true,
                'sort_order' => 0,
            ],
        );

        $scope->styles()->syncWithoutDetaching([$style->id]);
    }
}
