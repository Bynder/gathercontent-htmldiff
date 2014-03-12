<?php

use Gathercontent\Htmldiff\Htmldiff;

class HtmldiffTest extends PHPUnit_Framework_TestCase
{

    public function testStringDiff()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->compare("Test", "Testing", true);

        $this->assertEquals("<del>Test</del><ins>Testing</ins>", $result);
    }
}
