<?php

namespace Tests\Unit;

use App\Models\Business;
use App\Services\FestivalAiBusinessContextService;
use Tests\TestCase;

class FestivalAiBusinessContextServiceTest extends TestCase
{
    public function test_it_excludes_hidden_business_name_and_logo_from_the_ai_snapshot(): void
    {
        $business = new Business([
            'name' => 'Artera Pixel',
            'logo' => 'logos/artera.png',
            'mobile_no' => '9876543210',
            'email' => 'hello@example.test',
            'website' => 'example.test',
            'address' => 'Surat',
            'hidden_frame_fields' => [
                'business_name' => true,
                'logo' => true,
                'mobile_numbers' => ['9876543210'],
            ],
        ]);
        $business->id = 25;

        $snapshot = app(FestivalAiBusinessContextService::class)->snapshotForBusiness($business);

        $this->assertSame(25, $snapshot['business_id']);
        $this->assertArrayNotHasKey('name', $snapshot);
        $this->assertArrayNotHasKey('logo_path', $snapshot);
        $this->assertArrayNotHasKey('phones', $snapshot);
        $this->assertSame(['hello@example.test'], $snapshot['emails']);
    }

    public function test_it_keeps_visible_business_name_and_logo_in_the_ai_snapshot(): void
    {
        $business = new Business([
            'name' => 'Artera Pixel',
            'logo' => 'logos/artera.png',
            'hidden_frame_fields' => [],
        ]);

        $snapshot = app(FestivalAiBusinessContextService::class)->snapshotForBusiness($business);

        $this->assertSame('Artera Pixel', $snapshot['name']);
        $this->assertSame('logos/artera.png', $snapshot['logo_path']);
    }
}
