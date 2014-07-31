<?php

/*
 * Here we try to end up with an output that looks like Git's diff.
 * It's a modified version of \cogpowered\FineDiff\Render\Html
 */

namespace GatherContent\Htmldiff;

use \cogpowered\FineDiff\Parser\OpcodeInterface;
use \cogpowered\FineDiff\Render\Renderer;

class GitDiffRenderer extends Renderer
{
    public function callback($opcode, $from, $from_offset, $from_len)
    {
        if ($opcode === 'c') {

            $str = ' '.implode("\n ", explode("\n", substr($from, $from_offset, $from_len)))."\n";
            $str = rtrim($str, "\n ")."\n";

        } elseif ($opcode === 'd') {

            $deletion = substr($from, $from_offset, $from_len);

            if (strcspn($deletion, " \n\r") === 0) {

                $deletion = str_replace(array("\n","\r"), array('\n','\r'), $deletion);
            }

            $str = '-'.implode("\n-", explode("\n", $deletion))."\n";
            $str = rtrim($str, "-\n")."\n";

        } else /* if ( $opcode === 'i' ) */ {

            $str = '+'.implode("\n+", explode("\n", substr($from, $from_offset, $from_len)))."\n";
            $str = rtrim($str, "+\n")."\n";

        }

        return $str;
    }
}
