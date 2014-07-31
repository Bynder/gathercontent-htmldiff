<?php

namespace GatherContent\Htmldiff;

use \cogpowered\FineDiff\Diff;
use \cogpowered\FineDiff\Granularity\Paragraph as Granularity;

class Differ implements DifferInterface
{
    private $diff;

    public function __construct(Processor $processor = null, Diff $diff = null)
    {
        $this->diff = $diff ?: new Diff(new Granularity, new GitDiffRenderer);
    }

    public function diff($old, $new)
    {
        return $this->diff->render($old, $new);
    }
}
