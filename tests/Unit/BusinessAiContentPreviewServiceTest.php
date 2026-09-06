<?php

namespace Tests\Unit;

use App\Services\BusinessAiContentPreviewService;
use Tests\TestCase;

class BusinessAiContentPreviewServiceTest extends TestCase
{
    public function test_it_keeps_user_editable_copy_but_preserves_server_source_summary(): void
    {
        $service = app(BusinessAiContentPreviewService::class);
        $preview = $service->normaliseSubmitted([
            'headline' => '  Eye care reminder  ',
            'content' => '  Book a vision check today.  ',
            'cta' => 'Call the clinic',
            'content_lines' => ['Vision check', 'Doctor consultation'],
        ], [
            'headline' => 'Fallback heading',
            'content' => 'Fallback content',
            'cta' => 'Fallback CTA',
            'content_lines' => ['Fallback content'],
            'source_summary' => ['uses_general_data' => [['text' => 'Approved point']]],
        ]);

        $this->assertSame('Eye care reminder', $preview['headline']);
        $this->assertSame('Book a vision check today.', $preview['content']);
        $this->assertSame(['Vision check', 'Doctor consultation'], $preview['content_lines']);
        $this->assertSame([['text' => 'Approved point']], $preview['source_summary']['uses_general_data']);
    }
}
