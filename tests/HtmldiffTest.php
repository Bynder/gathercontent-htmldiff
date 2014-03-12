<?php

use Gathercontent\Htmldiff\Htmldiff;

class HtmldiffTest extends PHPUnit_Framework_TestCase
{

    public function testStringDiff()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->compare("Test", "Testing", true);

        $expected = '<del class="html-diff-del">Test</del><ins class="html-diff-ins">Testing</ins>';
        $this->assertEquals($expected, $result);
    }
}
