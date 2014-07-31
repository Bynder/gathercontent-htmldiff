<?php

namespace GatherContent\Htmldiff;

interface DifferInterface
{
    public function diff($old, $new);
}
