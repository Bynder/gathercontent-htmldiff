<?php

/**
 * FINE granularity DIFF
 *
 * Computes a set of instructions to convert the content of
 * one string into another.
 *
 * Originally created by Raymond Hill (https://github.com/gorhill/PHP-FineDiff), brought up
 * to date by Cog Powered (https://github.com/cogpowered/FineDiff).
 *
 * @copyright Copyright 2011 (c) Raymond Hill (http://raymondhill.net/blog/?p=441)
 * @copyright Copyright 2013 (c) Robert Crowe (http://cogpowered.com)
 * @link https://github.com/cogpowered/FineDiff
 * @version 0.0.1
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Gathercontent\Htmldiff\Render;

use cogpowered\FineDiff\Parser\OpcodeInterface;

class CustomRenderer extends \cogpowered\FineDiff\Render\Renderer
{
    public function callback($opcode, $from, $from_offset, $from_len)
    {
        if ($opcode === 'c') {
            $str = ' '.implode("\n ",explode("\n",substr($from, $from_offset, $from_len)))."\n";
            $str = rtrim($str,"\n ")."\n";
        } else if ($opcode === 'd') {

            $deletion = substr($from, $from_offset, $from_len);

            if ( strcspn($deletion, " \n\r") === 0 ) {
                $deletion = str_replace(array("\n","\r"), array('\n','\r'), $deletion);
            }

            $str = '-'.implode("\n-",explode("\n",$deletion))."\n";
            $str = rtrim($str, "-\n")."\n";

        } else /* if ( $opcode === 'i' ) */ {
            $str = '+'.implode("\n+",explode("\n",substr($from, $from_offset, $from_len)))."\n";
            $str = rtrim($str, "+\n")."\n";
        }

        return $str;
    }
}
