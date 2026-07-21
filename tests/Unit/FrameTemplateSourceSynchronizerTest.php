<?php

namespace Tests\Unit;

use App\Services\FrameTemplateSourceSynchronizer;
use Tests\TestCase;

class FrameTemplateSourceSynchronizerTest extends TestCase
{
    public function test_it_identifies_and_names_canonical_frame_json(): void
    {
        $synchronizer = new FrameTemplateSourceSynchronizer();

        $this->assertSame(
            'json/Frame_PEWA_0010_3.json',
            $synchronizer->canonicalZipEntry('Frame_PEWA_0010_3'),
        );
        $this->assertSame(
            ['layers' => []],
            $synchronizer->decodeTemplateJson('{"layers":[]}'),
        );
        $this->assertNull($synchronizer->decodeTemplateJson('{"manifest":"metadata"}'));
    }
}
