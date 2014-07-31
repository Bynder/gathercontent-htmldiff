<?php

namespace GatherContent\Htmldiff;

class Htmldiff
{
    private $processor;
    private $differ;

    public function __construct(ProcessorInterface $processor = null, DifferInterface $differ = null)
    {
        $this->processor = $processor ?: new Processor;
        $this->differ    = $differ    ?: new Differ;

        $this->checkForTidy();
    }

    private function checkForTidy()
    {
        if (!extension_loaded('tidy')) {

            throw new \Exception('Required Tidy extension not found.', 1);
        }
    }

    public function diff($old, $new)
    {
        $old = $this->processor->prepareHtmlInput($old);
        $new = $this->processor->prepareHtmlInput($new);

        $diff = $this->differ->diff($old, $new);

        return $this->processor->prepareHtmlOutput($diff);
    }
}
