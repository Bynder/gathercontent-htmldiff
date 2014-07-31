<?php

namespace GatherContent\Htmldiff;

interface ProcessorInterface
{
    public function prepareHtmlInput($input);
    public function prepareHtmlOutput($output);
}
