<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_purposes')) {
            Schema::create('business_ai_purposes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('key', 80)->unique();
                $table->string('title', 150);
                $table->string('icon', 100)->nullable();
                $table->string('description', 300)->nullable();
                $table->text('base_prompt');
                $table->json('brief_fields');
                $table->boolean('status')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('business_ai_styles')) {
            Schema::create('business_ai_styles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_ai_purpose_id');
                $table->string('key', 80);
                $table->string('name', 150);
                $table->string('description', 300)->nullable();
                $table->text('prompt_text');
                $table->json('colors')->nullable();
                $table->string('preview_image')->nullable();
                $table->boolean('status')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['business_ai_purpose_id', 'key'], 'business_ai_style_purpose_key_uq');
                $table->foreign('business_ai_purpose_id', 'business_ai_styles_purpose_fk')
                    ->references('id')->on('business_ai_purposes')->onDelete('cascade');
            });
        }

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('business_ai_styles');
        Schema::dropIfExists('business_ai_purposes');
    }

    /**
     * These records make a fresh install immediately usable. From this point
     * onward the Admin panel is the source of truth; the mobile app never
     * reads this default list directly.
     */
    private function seedDefaults(): void
    {
        if (DB::table('business_ai_purposes')->exists()) {
            return;
        }

        $purposes = [
            ['key' => 'hiring', 'title' => 'Hiring', 'icon' => 'work', 'description' => 'Job posts and recruitment', 'base_prompt' => 'Create a clear recruitment campaign visual.', 'brief_fields' => [
                ['key' => 'job_role', 'label' => 'Job role', 'required' => true, 'hint' => 'Sales Executive'],
                ['key' => 'location', 'label' => 'Location', 'required' => true, 'hint' => 'Surat, Gujarat'],
                ['key' => 'experience', 'label' => 'Experience', 'required' => false, 'hint' => '1–3 years'],
                ['key' => 'salary', 'label' => 'Salary / month', 'required' => false, 'hint' => '₹20,000 – ₹25,000'],
                ['key' => 'employment_type', 'label' => 'Employment type', 'required' => false, 'hint' => 'Full-time'],
            ]],
            ['key' => 'promotion', 'title' => 'Promotion / Offer', 'icon' => 'local_offer', 'description' => 'Discounts, offers and campaigns', 'base_prompt' => 'Create a premium offer or sales campaign visual.', 'brief_fields' => [
                ['key' => 'offer_title', 'label' => 'Offer title', 'required' => true, 'hint' => 'Monsoon Sale'],
                ['key' => 'offer_detail', 'label' => 'Offer detail', 'required' => true, 'hint' => 'Up to 40% off'],
                ['key' => 'valid_until', 'label' => 'Valid until', 'required' => false, 'hint' => '31 August'],
            ]],
            ['key' => 'product', 'title' => 'Product Post', 'icon' => 'inventory', 'description' => 'Showcase products and features', 'base_prompt' => 'Create a clean product showcase visual.', 'brief_fields' => [
                ['key' => 'product_name', 'label' => 'Product name', 'required' => true, 'hint' => 'Premium Leather Bag'],
                ['key' => 'key_feature', 'label' => 'Key feature', 'required' => false, 'hint' => 'Handcrafted genuine leather'],
                ['key' => 'price', 'label' => 'Price', 'required' => false, 'hint' => '₹2,499'],
            ]],
            ['key' => 'announcement', 'title' => 'Announcement / Event', 'icon' => 'campaign', 'description' => 'News, launches and events', 'base_prompt' => 'Create a business announcement or event visual.', 'brief_fields' => [
                ['key' => 'announcement', 'label' => 'Announcement', 'required' => true, 'hint' => 'We are opening a new branch'],
                ['key' => 'date', 'label' => 'Date', 'required' => false, 'hint' => '15 August'],
                ['key' => 'venue', 'label' => 'Venue', 'required' => false, 'hint' => 'Vesu, Surat'],
            ]],
            ['key' => 'service', 'title' => 'Service / Awareness', 'icon' => 'volunteer_activism', 'description' => 'Services, awareness and education', 'base_prompt' => 'Create a trustworthy service or awareness visual.', 'brief_fields' => [
                ['key' => 'service_name', 'label' => 'Service name', 'required' => true, 'hint' => 'Home Interior Design'],
                ['key' => 'benefit', 'label' => 'Customer benefit', 'required' => true, 'hint' => 'Free site visit'],
            ]],
            ['key' => 'custom', 'title' => 'Custom AI Post', 'icon' => 'auto_awesome', 'description' => 'Describe anything and AI creates it', 'base_prompt' => 'Create a polished custom business marketing visual.', 'brief_fields' => [
                ['key' => 'main_message', 'label' => 'What should this post say?', 'required' => true, 'hint' => 'Describe your business post'],
            ]],
        ];
        $styles = [
            ['key' => 'modern_corporate', 'name' => 'Modern Corporate', 'description' => 'Clean, premium and confident', 'prompt_text' => 'Clean premium corporate composition, deep navy and indigo palette, confident editorial lighting and clear whitespace.', 'colors' => ['#4338CA', '#0F172A']],
            ['key' => 'bold_recruitment', 'name' => 'Bold Recruitment', 'description' => 'High contrast and energetic', 'prompt_text' => 'High contrast energetic campaign composition, dark foundation, vivid violet accents and a bold contemporary visual hierarchy.', 'colors' => ['#0F172A', '#7C3AED']],
            ['key' => 'minimal_professional', 'name' => 'Minimal Professional', 'description' => 'Calm whitespace and elegance', 'prompt_text' => 'Minimal professional art direction with generous whitespace, soft neutral light and refined premium restraint.', 'colors' => ['#E2E8F0', '#334155']],
            ['key' => 'creative_dynamic', 'name' => 'Creative Dynamic', 'description' => 'Contemporary colour and movement', 'prompt_text' => 'Contemporary dynamic art direction with vivid colour movement, polished advertising lighting and balanced negative space.', 'colors' => ['#7C3AED', '#F97316']],
        ];
        $now = now();
        foreach ($purposes as $purposeIndex => $purpose) {
            $fields = $purpose['brief_fields'];
            unset($purpose['brief_fields']);
            $purposeId = DB::table('business_ai_purposes')->insertGetId([
                ...$purpose,
                'brief_fields' => json_encode($fields),
                'status' => true,
                'sort_order' => $purposeIndex + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($styles as $styleIndex => $style) {
                DB::table('business_ai_styles')->insert([
                    'business_ai_purpose_id' => $purposeId,
                    ...$style,
                    'colors' => json_encode($style['colors']),
                    'status' => true,
                    'sort_order' => $styleIndex + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
