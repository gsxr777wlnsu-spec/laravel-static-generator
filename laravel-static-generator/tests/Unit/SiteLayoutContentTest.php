<?php

namespace Tests\Unit;

use App\Support\SiteLayoutContent;
use Tests\TestCase;

class SiteLayoutContentTest extends TestCase
{
    public function test_section_sanitizer_removes_xml_processing_instruction_without_wrapping_section(): void
    {
        $html = '<?xml encoding="UTF-8"><section class="steps steps--demo" id="steps"><p>Text</p></section>';

        $result = app(SiteLayoutContent::class)->sanitizeSectionHtml($html);

        $this->assertSame('<section class="steps steps--demo" id="steps"><p>Text</p></section>', $result);
    }

    public function test_section_sanitizer_unwraps_duplicate_section_with_xml_comment(): void
    {
        $html = '<section class="benefits"><!--?xml encoding="UTF-8"--><section class="benefits benefits--demo" id="benefits"><p>Text</p></section></section>';

        $result = app(SiteLayoutContent::class)->sanitizeSectionHtml($html);

        $this->assertSame('<section class="benefits benefits--demo" id="benefits"><p>Text</p></section>', $result);
    }
}
